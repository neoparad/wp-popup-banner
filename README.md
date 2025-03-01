# WordPress ポップアップバナープラグイン

ページを読み進めている途中で画面中央にポップアップするバナーを表示するための WordPress プラグインです。

## 機能

- 管理画面に「バナー」メニューを設置し、複数のバナーを管理可能
- ACF カスタムフィールドで詳細設定
- 表示ページの選択が可能（固定ページ、記事ページ、カテゴリーページ、タグページ、特定のページID）
- 各ページタイプで除外ページも指定可能
- バナー画像のアップロード（レスポンシブ対応）
- 表示タイミングを設定可能（秒数指定 or スクロール位置指定）
- バナーの右上に閉じるボタンを設置
- パフォーマンスを最適化（非同期読み込み、遅延DOM生成など）

## 必要環境

- WordPress 5.0 以上
- PHP 7.0 以上
- Advanced Custom Fields (ACF) プラグイン

## インストール方法

1. リポジトリをダウンロードして解凍するか、`git clone` でダウンロード
2. `popup` フォルダを WordPress の plugins ディレクトリにアップロード
3. WordPress 管理画面からプラグインを有効化
4. 管理画面の「バナー」メニューからバナーを追加・設定

## 使い方

1. 管理画面の「バナー」→「新規バナー追加」をクリック
2. バナータイトルを入力
3. バナー画像をアップロード
4. 必要に応じてバナーリンクを設定
5. 「表示設定」タブで表示するページを選択
6. 「表示タイミング」タブで表示するタイミングを設定
7. 「ステータス」タブでバナーを有効化
8. 「公開」ボタンをクリック

## カスタマイズ

スタイルやスクリプトは以下のファイルで編集できます：

- CSS: `css/popup-banner.css`
- JavaScript: `js/popup-banner.js`

## 貢献

バグ報告や機能リクエストは GitHub の Issue で受け付けています。プルリクエストも歓迎します。

## ライセンス

GPL v2 以降
