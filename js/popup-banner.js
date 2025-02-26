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