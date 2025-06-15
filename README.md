README

# アプリケーション名

お問い合わせフォーム

## 使用技術(実行環境)

- OS：Windows 11
- フレームワーク：Laravel 8.x
- プログラミング言語：PHP 8.x
- コンテナ管理：Docker
- データベース： MySQL 8.0.x
- バージョン管理：Git / GitHub
- メール開発環境：MailHog
- 決済サービス：Stripe
- フロントエンド：一部JavaScript（ES6相当のバニラJS） を使用（画像の非同期アップロード処理など）

## 環境構築

- Dockerビルド
1. git clone リンク：git@github.com:dasayo1215/Case1_FleaMarketApp.git
2. DockerDesktopアプリを立ち上げる
3. docker-compose up -d --build
*MySQLは、OSによって起動しない場合があるのでそれぞれのPCに合わせてdocker-compose.ymlファイルを編集してください。

- Laravel環境構築
1. docker-compose exec php bash
2. composer install
3. .env.exampleファイルの名前を変更して.envファイルを作成。
4. php artisan key:generate
5. php artisan storage:link
6. php artisan migrate
7. php artisan db:seed
8. （任意）キャッシュクリアや設定の最適化

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## MailHog の利用について

- メール送信機能の動作確認はMailHogのWeb UI（http://localhost:8025/）で行います。
- Laravelの `.env` にメール送信設定は以下のようにしてあります。

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

- これにより、ローカルのメール送信はMailHog経由となり、実際に外部には送信されません。
- メール認証や通知メールを受け取ったかどうかは、MailHogのUIで確認できます。

## Stripe の設定について

1. [Stripe](https://dashboard.stripe.com/register)でアカウントを作成してください（テストモードでOK）。
2. ダッシュボードからAPIキー（公開可能キーとシークレットキー）を取得してください。
3. プロジェクトの `.env` ファイルに以下の環境変数を追加します。
（STRIPE_KEYに公開可能キー、STRIPE_SECRETにシークレットキー）

```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
```

※　`.env` はGit管理から除外しています。APIキーは絶対に公開しないよう注意してください。

## ngrok と webhook の設定について（毎回設定）

支払いテストの実行のため、ngrokを利用します。
※あくまで開発専用で、本番環境では使わないようにしてください。

### 事前準備：ngrokのインストール

1. [ngrok公式サイト](https://ngrok.com/)にアクセスし、無料アカウントを作成してください。
2. 公式サイトの「Setup & Installation」セクションに従い、OSに合った方法でngrokをインストールしてください。
（Homebrew、またはzipファイルを直接ダウンロードして展開 など）
※zipファイルからインストールした場合は、必要に応じてngrokの実行ファイルがあるフォルダをPATHに追加してください。  
4. 公式サイトに従い、ターミナルで以下コマンドを実行し、ngrokにアカウントの認証情報（Authtoken）を設定します。

```bash
ngrok config add-authtoken YOUR_AUTHTOKEN
```
※Authtoken（上記でいうYOUR_AUTHTOKEN）はngrok公式サイトの「Setup & Installation」セクションで確認できます。

### ngrokトンネルの起動手順
1. 以下のコマンドでローカルのポート80番を公開します。

```bash
ngrok http 80
```

2. 出力されるURLをコピーしてください。

```nginx
Forwarding  https://3092-xx-xx-xx.ngrok-free.app -> http://localhost:80
```

※上記の「https://3092-xx-xx-xx.ngrok-free.app」の部分をコピーします。

3. Stripeダッシュボードの左サイドバーから 「開発者」 > 「Webhook」 にアクセスし、「+ エンドポイントを追加」をクリックします。

4. 「受信するイベントの選択」で以下の3つを選択して「続行」を押下します。
・checkout.session.async_payment_failed
・checkout.session.async_payment_succeeded
・checkout.session.completed

5. 「エンドポイントの設定」画面で、送信先タイプに 「Webhookエンドポイント」 を選択して「続行」を押下します。

6. エンドポイントURLには、手順2でコピーしたURLの末尾に /webhook/stripe を付けて入力します。
   例）https://xxxx.ngrok-free.app/webhook/stripe
   ※「送信先名」などの項目は任意で入力してください。
   以上で送信先の設定が完了します。

7. テストが終わったら、Ctrl+Cなどでトンネルを停止してください。
   ※次回再度使用する場合は ngrok http 80 を実行して、新しいURLを取得しなおしてください。

## Stripe CLI の設定について

1. docker run --rm -it -v ~/.config/stripe:/root/.config/stripe stripe/stripe-cli login
指示に従い、出力されたURLに接続して認証を行います。
2. docker-compose logs stripe-cli
下記のようにキーが出力される。
   
```bash
> stripe-cli  | Ready! You are using Stripe API Version [2025-04-30.basil]. Your webhook signing secret is whsec_xxxxxxxxxxxxxxxxxxxxx (^C to quit)
```

3. プロジェクトの `.env` ファイルに以下の環境変数を追加します。

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
```

## 環境構築時の注意点
"Permission denied" 関係のエラーが出た際は、まず下記を試してください。

```bash
sudo chmod -R 777 src/*
```

※ chmod 777 はすべてのユーザーに書き込み権限を与えるため、セキュリティ上リスクがあります。
開発環境での一時的な対処としてのみ使用し、本番環境では適切な権限設定をおすすめします。

## 支払いテストについて

1. Stripeのテストカード番号は `4242 4242 4242 4242`（任意の有効期限・CVCで利用可能）です。
2. 失敗用カードは、、
3. コンビニ支払いのテストには。テスト用電話番号 `11111111110` を利用できます。3分後に決済が完了したとみなされます。

## URL

- 開発環境：http://localhost/
- phpMyAdmin：http://localhost:8080/
- MailHog UI: http://localhost:8025/
※ローカル環境で送信された認証メールや通知メールを確認できます。
- Stripe Dashboard：https://dashboard.stripe.com/test
※テストモードでの支払い状況やWebhookイベントの確認に使います。

## PHPUnit テストの実行方法（当プロジェクト用）

当プロジェクトでは、テスト環境を `.env.testing` ファイルおよび `config/database.php` の `mysql_test` 接続で構築しています。

### テスト環境構成

#### `.env.testing`（抜粋）

```env
APP_ENV=test
APP_KEY=
APP_DEBUG=true

DB_CONNECTION=mysql_test
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=demo_test
DB_USERNAME=root
DB_PASSWORD=root

MAIL_MAILER=smtp
MAIL_HOST=mail
MAIL_PORT=1025
MAIL_FROM_ADDRESS=coachtech@example.com
MAIL_FROM_NAME="COACHTECHフリマ 認証"
```
※ .env.testing は .env をコピーして編集してください。

#### config/database.php の connections に以下を追加（抜粋）
'mysql_test' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => 'demo_test',
    'username' => 'root',
    'password' => 'root',
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],

### テスト実行手順

#### ① APP_KEY の生成（初回のみ）
```
php artisan key:generate --env=testing
php artisan config:clear
```

#### ② テストの実行（すべてのテストを実行）
```
php artisan test
```

### Stripe のテスト設定について
`.env.testing` ではセキュリティ上の理由から、以下の Stripe 関連のキーは空欄になっています。

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

もし Stripe を使用した決済処理や Webhook のテストを行う場合は、.env（開発環境）と同様に、各種キーを .env.testing に設定してください。

### 補足事項
- 各Featureテストクラスで use RefreshDatabase; を使用しているため、テストごとに自動でマイグレーションが走ります。
- Seederは各テスト内で必要なものだけを呼び出す構成です。
- phpunit.xml は編集・使用しておらず、.env.testing の設定で切り替え管理しています。
- 誤って .env の本番DBを使わないよう注意してください。

## その他
- ItemsSeederにおいて、指定してあるダミーの商品データにカテゴリー情報を追加しました。
- UsersSeederでは、初年度でのユーザー数1,000人達成を目標としているため、ユーザー数を 1,000人 とした場合のテストを実施した。なお、現状のUsersSeederは100人に設定してある。
- メール認証の仕様：
- 新規ユーザー登録時にのみ、確認メールを自動送信します。
- それ以外の動作で未認証が検出された場合は、認証を促すページにリダイレクトされます（メールは自動送信されません）。
- 表示確認テストについて：
- Chrome および Firefox は、自身のPCにインストールされているブラウザを使用して表示確認を行いました。
- Safari の表示確認は、playwright ディレクトリにて以下のコマンドを実行することで実施しました：

```bash
npm run test:safari
```

- paid_atカラムで管理

## ER図
![Case1 drawio](https://github.com/user-attachments/assets/1bcdbaa6-310f-47af-944f-19104026ab87)


## 画面例
- 登録ページ


