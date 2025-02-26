<?php
/**
 * Plugin Name: ポップアップバナー
 * Description: ページを読み進めている途中で表示されるポップアップバナーを管理します
 * Version: 1.0
 * Author: Your Name
 */

// 直接アクセス禁止
if (!defined('ABSPATH')) {
    exit;
}

// プラグインのメインクラス
class Popup_Banner {

    public function __construct() {
        // ACFが有効かどうかを確認
        add_action('admin_init', array($this, 'check_acf'));
        
        // カスタム投稿タイプを登録
        add_action('init', array($this, 'register_banner_post_type'));
        
        // ACFフィールドを登録
        add_action('acf/init', array($this, 'register_acf_fields'));
        
        // フロントエンドにスクリプトとスタイルを追加
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // フッターにバナーHTMLを追加
        add_action('wp_footer', array($this, 'render_banners'));
    }

    // ACFが有効かどうかを確認する関数
    public function check_acf() {
        if (!class_exists('ACF')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('ポップアップバナープラグインには、Advanced Custom Fields (ACF) プラグインが必要です。', 'popup-banner'); ?></p>
                </div>
                <?php
            });
        }
    }

    // カスタム投稿タイプを登録する関数
    public function register_banner_post_type() {
        $labels = array(
            'name'               => 'バナー',
            'singular_name'      => 'バナー',
            'menu_name'          => 'バナー',
            'name_admin_bar'     => 'バナー',
            'add_new'            => '新規バナー追加',
            'add_new_item'       => '新規バナーを追加',
            'new_item'           => '新規バナー',
            'edit_item'          => 'バナーを編集',
            'view_item'          => 'バナーを表示',
            'all_items'          => 'すべてのバナー',
            'search_items'       => 'バナーを検索',
            'not_found'          => 'バナーが見つかりません',
            'not_found_in_trash' => 'ゴミ箱にバナーはありません'
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-megaphone',
            'supports'           => array('title')
        );

        register_post_type('popup_banner', $args);
    }

    // ACFフィールドを登録する関数
    public function register_acf_fields() {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_popup_banner',
                'title' => 'バナー設定',
                'fields' => array(
                    // バナー画像
                    array(
                        'key' => 'field_banner_image',
                        'label' => 'バナー画像',
                        'name' => 'banner_image',
                        'type' => 'image',
                        'instructions' => '表示するバナー画像をアップロードしてください。',
                        'required' => 1,
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ),
                    // バナーリンク
                    array(
                        'key' => 'field_banner_link',
                        'label' => 'バナーリンク',
                        'name' => 'banner_link',
                        'type' => 'url',
                        'instructions' => 'バナーをクリックした時の遷移先URLを入力してください。',
                        'required' => 0,
                    ),
                    // 表示条件タブ
                    array(
                        'key' => 'field_display_tab',
                        'label' => '表示設定',
                        'name' => '',
                        'type' => 'tab',
                        'placement' => 'top',
                    ),
                    // 表示ページ設定
                    array(
                        'key' => 'field_display_pages',
                        'label' => '表示ページ',
                        'name' => 'display_pages',
                        'type' => 'checkbox',
                        'instructions' => '表示するページタイプを選択してください。',
                        'required' => 0,
                        'choices' => array(
                            'page' => '固定ページ全て',
                            'post' => '記事ページ全て',
                            'category' => 'カテゴリーページ全て',
                            'tag' => 'タグページ全て',
                            'specific' => '特定のページ（IDを指定）',
                        ),
                        'default_value' => array(),
                        'layout' => 'vertical',
                        'toggle' => 0,
                        'return_format' => 'value',
                    ),
                    // 除外ページ（固定ページ）
                    array(
                        'key' => 'field_exclude_pages',
                        'label' => '除外する固定ページ',
                        'name' => 'exclude_pages',
                        'type' => 'text',
                        'instructions' => '除外する固定ページIDをカンマ区切りで入力してください。（例：1,2,3）',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_display_pages',
                                    'operator' => '==',
                                    'value' => 'page',
                                ),
                            ),
                        ),
                    ),
                    // 除外ページ（記事ページ）
                    array(
                        'key' => 'field_exclude_posts',
                        'label' => '除外する記事ページ',
                        'name' => 'exclude_posts',
                        'type' => 'text',
                        'instructions' => '除外する記事ページIDをカンマ区切りで入力してください。（例：1,2,3）',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_display_pages',
                                    'operator' => '==',
                                    'value' => 'post',
                                ),
                            ),
                        ),
                    ),
                    // 除外ページ（カテゴリーページ）
                    array(
                        'key' => 'field_exclude_categories',
                        'label' => '除外するカテゴリーページ',
                        'name' => 'exclude_categories',
                        'type' => 'text',
                        'instructions' => '除外するカテゴリーIDをカンマ区切りで入力してください。（例：1,2,3）',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_display_pages',
                                    'operator' => '==',
                                    'value' => 'category',
                                ),
                            ),
                        ),
                    ),
                    // 除外ページ（タグページ）
                    array(
                        'key' => 'field_exclude_tags',
                        'label' => '除外するタグページ',
                        'name' => 'exclude_tags',
                        'type' => 'text',
                        'instructions' => '除外するタグIDをカンマ区切りで入力してください。（例：1,2,3）',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_display_pages',
                                    'operator' => '==',
                                    'value' => 'tag',
                                ),
                            ),
                        ),
                    ),
                    // 特定のページID
                    array(
                        'key' => 'field_specific_pages',
                        'label' => '特定のページID',
                        'name' => 'specific_pages',
                        'type' => 'text',
                        'instructions' => '表示する特定のページIDをカンマ区切りで入力してください。（例：1,2,3）',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_display_pages',
                                    'operator' => '==',
                                    'value' => 'specific',
                                ),
                            ),
                        ),
                    ),
                    // 表示タイミング設定タブ
                    array(
                        'key' => 'field_timing_tab',
                        'label' => '表示タイミング',
                        'name' => '',
                        'type' => 'tab',
                        'placement' => 'top',
                    ),
                    // 表示タイミングタイプ
                    array(
                        'key' => 'field_timing_type',
                        'label' => '表示タイミング',
                        'name' => 'timing_type',
                        'type' => 'select',
                        'instructions' => 'バナーを表示するタイミングを選択してください。',
                        'required' => 1,
                        'choices' => array(
                            'seconds' => '秒数指定',
                            'scroll' => 'スクロール位置指定',
                        ),
                        'default_value' => 'seconds',
                        'return_format' => 'value',
                    ),
                    // 秒数指定
                    array(
                        'key' => 'field_seconds',
                        'label' => '秒数',
                        'name' => 'seconds',
                        'type' => 'number',
                        'instructions' => 'ページを開いてから何秒後に表示するかを指定してください。',
                        'required' => 1,
                        'default_value' => 5,
                        'min' => 1,
                        'max' => 60,
                        'step' => 1,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_timing_type',
                                    'operator' => '==',
                                    'value' => 'seconds',
                                ),
                            ),
                        ),
                    ),
                    // スクロール位置指定
                    array(
                        'key' => 'field_scroll_percentage',
                        'label' => 'スクロール位置（%）',
                        'name' => 'scroll_percentage',
                        'type' => 'number',
                        'instructions' => 'ページのどの位置までスクロールしたら表示するかをパーセンテージで指定してください。',
                        'required' => 1,
                        'default_value' => 30,
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_timing_type',
                                    'operator' => '==',
                                    'value' => 'scroll',
                                ),
                            ),
                        ),
                    ),
                    // ステータス設定
                    array(
                        'key' => 'field_status_tab',
                        'label' => 'ステータス',
                        'name' => '',
                        'type' => 'tab',
                        'placement' => 'top',
                    ),
                    // 有効/無効
                    array(
                        'key' => 'field_banner_status',
                        'label' => 'バナーステータス',
                        'name' => 'banner_status',
                        'type' => 'true_false',
                        'instructions' => 'バナーを有効にするには、オンにしてください。',
                        'required' => 0,
                        'default_value' => 1,
                        'ui' => 1,
                        'ui_on_text' => '有効',
                        'ui_off_text' => '無効',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'popup_banner',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));
        }
    }

    // フロントエンドにスクリプトとスタイルを追加する関数
    public function enqueue_scripts() {
        // バナーが有効なものがあるか確認
        $banners = $this->get_active_banners();
        if (empty($banners)) {
            return;
        }

        // スタイルの追加（非同期読み込み用のpreloadを使用）
        add_action('wp_head', function() {
            echo '<link rel="preload" href="' . plugin_dir_url(__FILE__) . 'css/popup-banner.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
            echo '<noscript><link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . 'css/popup-banner.css"></noscript>';
        }, 1);

        // スクリプトの追加（defer属性を使用）
        wp_enqueue_script(
            'popup-banner-script',
            plugin_dir_url(__FILE__) . 'js/popup-banner.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // deferを追加
        add_filter('script_loader_tag', function($tag, $handle, $src) {
            if ('popup-banner-script' === $handle) {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 3);

        // バナーデータをJSに渡す
        wp_localize_script(
            'popup-banner-script',
            'popupBannerData',
            array(
                'banners' => $banners
            )
        );
    }

    // 現在のページで表示すべきアクティブなバナーを取得する関数
    private function get_active_banners() {
        $args = array(
            'post_type' => 'popup_banner',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'banner_status',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );

        $banners_query = new WP_Query($args);
        $banners = array();

        if ($banners_query->have_posts()) {
            while ($banners_query->have_posts()) {
                $banners_query->the_post();
                $banner_id = get_the_ID();
                
                // このバナーが現在のページに表示すべきかチェック
                if ($this->should_display_banner($banner_id)) {
                    $image = get_field('banner_image', $banner_id);
                    $link = get_field('banner_link', $banner_id);
                    $timing_type = get_field('timing_type', $banner_id);
                    $seconds = get_field('seconds', $banner_id);
                    $scroll_percentage = get_field('scroll_percentage', $banner_id);

                    $banners[] = array(
                        'id' => $banner_id,
                        'image' => $image,
                        'link' => $link,
                        'timing_type' => $timing_type,
                        'seconds' => $seconds,
                        'scroll_percentage' => $scroll_percentage
                    );
                }
            }
            wp_reset_postdata();
        }

        return $banners;
    }

    // バナーを現在のページに表示すべきかどうかを判断する関数
    private function should_display_banner($banner_id) {
        $display_pages = get_field('display_pages', $banner_id);
        
        // 表示ページが設定されていない場合は表示しない
        if (empty($display_pages)) {
            return false;
        }

        // 現在のページタイプを取得
        $current_id = get_queried_object_id();
        
        // 各ページタイプに対するチェック
        foreach ($display_pages as $page_type) {
            switch ($page_type) {
                case 'page':
                    if (is_page()) {
                        $exclude_pages = get_field('exclude_pages', $banner_id);
                        if (!empty($exclude_pages)) {
                            $exclude_ids = array_map('trim', explode(',', $exclude_pages));
                            if (in_array($current_id, $exclude_ids)) {
                                continue;
                            }
                        }
                        return true;
                    }
                    break;
                    
                case 'post':
                    if (is_single()) {
                        $exclude_posts = get_field('exclude_posts', $banner_id);
                        if (!empty($exclude_posts)) {
                            $exclude_ids = array_map('trim', explode(',', $exclude_posts));
                            if (in_array($current_id, $exclude_ids)) {
                                continue;
                            }
                        }
                        return true;
                    }
                    break;
                    
                case 'category':
                    if (is_category()) {
                        $exclude_categories = get_field('exclude_categories', $banner_id);
                        if (!empty($exclude_categories)) {
                            $exclude_ids = array_map('trim', explode(',', $exclude_categories));
                            if (in_array($current_id, $exclude_ids)) {
                                continue;
                            }
                        }
                        return true;
                    }
                    break;
                    
                case 'tag':
                    if (is_tag()) {
                        $exclude_tags = get_field('exclude_tags', $banner_id);
                        if (!empty($exclude_tags)) {
                            $exclude_ids = array_map('trim', explode(',', $exclude_tags));
                            if (in_array($current_id, $exclude_ids)) {
                                continue;
                            }
                        }
                        return true;
                    }
                    break;
                    
                case 'specific':
                    $specific_pages = get_field('specific_pages', $banner_id);
                    if (!empty($specific_pages)) {
                        $specific_ids = array_map('trim', explode(',', $specific_pages));
                        if (in_array($current_id, $specific_ids)) {
                            return true;
                        }
                    }
                    break;
            }
        }
        
        return false;
    }

    // バナーHTMLをレンダリングする関数
    public function render_banners() {
        $banners = $this->get_active_banners();
        
        if (empty($banners)) {
            return;
        }
        
        // バナーのHTMLコンテナを出力
        echo '<div id="popup-banners-container"></div>';
    }
}

// プラグインのインスタンスを作成
$popup_banner = new Popup_Banner();

// プラグインの有効化時にCSSとJSディレクトリを作成
register_activation_hook(__FILE__, 'popup_banner_activation');
function popup_banner_activation() {
    // CSSディレクトリを作成
    $css_dir = plugin_dir_path(__FILE__) . 'css';
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
        
        // CSSファイルを作成
        $css_content = <<<CSS
.popup-banner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    will-change: opacity, visibility;
    contain: layout size paint;
}

.popup-banner-overlay.active {
    opacity: 1;
    visibility: visible;
}

.popup-banner-container {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    overflow: hidden;
}

.popup-banner-close {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
}

.popup-banner-close:before,
.popup-banner-close:after {
    content: '';
    position: absolute;
    width: 15px;
    height: 2px;
    background-color: white;
}

.popup-banner-close:before {
    transform: rotate(45deg);
}

.popup-banner-close:after {
    transform: rotate(-45deg);
}

.popup-banner-image {
    display: block;
    max-width: 100%;
    height: auto;
}

.popup-banner-link {
    display: block;
}
CSS;
        file_put_contents($css_dir . '/popup-banner.css', $css_content);
    }
    
    // JSディレクトリを作成
    $js_dir = plugin_dir_path(__FILE__) . 'js';
    if (!file_exists($js_dir)) {
        mkdir($js_dir, 0755, true);
        
        // JSファイルを作成
        $js_content = <<<JS
(function($) {
    'use strict';
    
    // Intersection Observerの設定
    let scrollObserver;
    if ('IntersectionObserver' in window) {
        scrollObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const bannerId = entry.target.dataset.bannerId;
                    const bannerElement = document.getElementById('popup-banner-' + bannerId);
                    if (bannerElement) {
                        // バナーの実際のDOMを遅延生成
                        createActualBanner(popupBannerData.banners.find(b => b.id.toString() === bannerId));
                        // オブザーバーを解除
                        scrollObserver.unobserve(entry.target);
                    }
                }
            });
        }, {
            rootMargin: '200px', // プリロード用のマージン
            threshold: 0.01
        });
    }
    
    // ページアイドル時にバナーを初期化する
    function initBannersOnIdle() {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(initBanners, { timeout: 2000 });
        } else {
            // requestIdleCallbackをサポートしていないブラウザ用のフォールバック
            setTimeout(initBanners, 200);
        }
    }
    
    // バナーの初期化処理
    function initBanners() {
        // バナーデータがある場合に処理
        if (typeof popupBannerData !== 'undefined' && popupBannerData.banners.length > 0) {
            // バナーのプレースホルダーを作成
            $.each(popupBannerData.banners, function(index, banner) {
                createBannerPlaceholder(banner);
            });
        }
    }
    
    // バナーのプレースホルダーを作成する関数
    function createBannerPlaceholder(banner) {
        const bannerId = 'popup-banner-' + banner.id;
        const placeholder = document.createElement('div');
        placeholder.id = bannerId;
        placeholder.className = 'popup-banner-overlay';
        placeholder.style.display = 'none';
        
        // 遅延読み込み用のトリガー要素
        const trigger = document.createElement('div');
        trigger.className = 'banner-scroll-trigger';
        trigger.dataset.bannerId = banner.id;
        trigger.style.height = '1px';
        trigger.style.width = '1px';
        trigger.style.position = 'absolute';
        trigger.style.visibility = 'hidden';
        
        document.getElementById('popup-banners-container').appendChild(placeholder);
        document.body.appendChild(trigger);
        
        // 表示タイミングの設定
        setDisplayTiming(banner, bannerId);
        
        // スクロールトリガーの監視を設定
        if (scrollObserver) {
            scrollObserver.observe(trigger);
        } else {
            // フォールバック：すぐにバナーを作成
            createActualBanner(banner);
        }
    }
    
    // 実際のバナーDOM要素を作成する関数（遅延実行）
    function createActualBanner(banner) {
        if (!banner) return;
        
        const bannerId = 'popup-banner-' + banner.id;
        const bannerElement = document.getElementById(bannerId);
        
        if (!bannerElement || bannerElement.querySelector('.popup-banner-container')) {
            return; // すでに作成済みか、要素がない
        }
        
        const containerDiv = document.createElement('div');
        containerDiv.className = 'popup-banner-container';
        
        const closeButton = document.createElement('div');
        closeButton.className = 'popup-banner-close';
        containerDiv.appendChild(closeButton);
        
        // 画像の用意
        const img = new Image();
        img.src = banner.image.url;
        img.alt = banner.image.alt || '';
        img.className = 'popup-banner-image';
        img.loading = 'lazy';
        img.width = banner.image.width;
        img.height = banner.image.height;
        
        // リンク付きの場合はaタグで囲む
        if (banner.link) {
            const link = document.createElement('a');
            link.href = banner.link;
            link.className = 'popup-banner-link';
            link.target = '_blank';
            link.appendChild(img);
            containerDiv.appendChild(link);
        } else {
            containerDiv.appendChild(img);
        }
        
        bannerElement.appendChild(containerDiv);
        bannerElement.style.display = ''; // 表示を元に戻す
        
        // 閉じるボタンのイベント設定
        closeButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            bannerElement.classList.remove('active');
            
            // バナーを閉じた状態をセッションストレージに保存
            try {
                sessionStorage.setItem('popup_banner_closed_' + banner.id, 'true');
            } catch (e) {
                // ストレージアクセスエラーは無視
            }
        });
    }
    
    // 表示タイミングの設定をする関数
    function setDisplayTiming(banner, bannerId) {
        // セッションストレージをチェックして、すでに閉じられたバナーは表示しない
        try {
            if (sessionStorage.getItem('popup_banner_closed_' + banner.id) === 'true') {
                return;
            }
        } catch (e) {
            // ストレージアクセスエラーは無視
        }
        
        if (banner.timing_type === 'seconds') {
            // 秒数指定の場合
            setTimeout(function() {
                const bannerElement = document.getElementById(bannerId);
                if (bannerElement) {
                    // バナーのDOM要素をここで作成
                    createActualBanner(banner);
                    // 少し遅らせて表示アニメーションを開始
                    setTimeout(function() {
                        bannerElement.classList.add('active');
                    }, 50);
                }
            }, banner.seconds * 1000);
        } else if (banner.timing_type === 'scroll') {
            // スクロール位置指定の場合 - パフォーマンスのためにスロットリングを適用
            let scrollTimeout;
            let scrollStarted = false;
            
            $(window).on('scroll', function() {
                // スクロールイベントをスロットリング
                if (scrollTimeout) {
                    return;
                }
                
                scrollTimeout = setTimeout(function() {
                    scrollTimeout = null;
                    
                    // スクロール位置をチェック
                    const scrollTop = $(window).scrollTop();
                    const docHeight = $(document).height();
                    const winHeight = $(window).height();
                    const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;
                    
                    if (!scrollStarted && scrollPercent >= banner.scroll_percentage) {
                        scrollStarted = true; // 一度だけ実行
                        
                        const bannerElement = document.getElementById(bannerId);
                        if (bannerElement) {
                            // バナーのDOM要素をここで作成
                            createActualBanner(banner);
                            // 少し遅らせて表示アニメーションを開始
                            setTimeout(function() {
                                bannerElement.classList.add('active');
                            }, 50);
                            
                            // このバナーのスクロールイベントを解除
                            $(window).off('scroll');
                        }
                    }
                }, 100); // 100msごとにスクロール位置をチェック
            });
        }
    }
    
    // ページ読み込み完了後にアイドル時に初期化
    if (document.readyState === 'complete') {
        initBannersOnIdle();
    } else {
        $(window).on('load', initBannersOnIdle);
    }
    
})(jQuery);
JS;
        file_put_contents($js_dir . '/popup-banner.js', $js_content);
    }
}