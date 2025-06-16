README

# アプリケーション名

coachtechフリマ

## 使用技術(実行環境)

- OS：Windows 11
- フレームワーク：Laravel 8.x
- プログラミング言語：PHP 7.x
- コンテナ管理：Docker
- データベース： MySQL 8.0.x
- バージョン管理：Git / GitHub
- メール開発環境：MailHog
- 決済サービス：Stripe
- フロントエンド：一部JavaScript（ES6相当のバニラJS） を使用（画像の非同期アップロード処理など）

## 環境構築

### リポジトリのクローンと Docker ビルド
- DockerDesktopアプリを立ち上げ、下記を実行してください。
```
git clone git@github.com:dasayo1215/Case1_FleaMarketApp.git
cd Case1_FleaMarketApp
docker-compose up -d --build
```
*MySQLは、OSによって起動しない場合があるのでそれぞれのPCに合わせてdocker-compose.ymlファイルを編集してください。

### Laravel環境構築
```
docker-compose exec php bash
```
- Laravelコンテナ内で以下を実行します：
```
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate
php artisan db:seed
```
#### （任意）キャッシュクリアや設定の最適化

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ログファイルと書き込み権限の設定
```bash
# ログファイルを手動で作成（存在しない場合）
touch src/storage/logs/laravel.log

# 所有者をWebサーバーに変更
sudo chown -R www-data:www-data src/storage

# 一時的な対処として全体に書き込み権限を付与（開発環境のみ）
sudo chmod -R 777 src/*
```

※ chmod 777 はセキュリティリスクがあるため、本番環境では適切なユーザーとグループ権限の設定を推奨します。

### Stripe の設定について

1. [Stripe](https://dashboard.stripe.com/register)でアカウントを作成してください（テストモードでOK）。
2. ダッシュボードからAPIキー（公開可能キーとシークレットキー）を取得してください。
3. プロジェクトの `.env` ファイルに以下の環境変数を追加します。
（STRIPE_KEYに公開可能キー、STRIPE_SECRETにシークレットキー）

```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
```

※　`.env` はGit管理から除外しています。APIキーは絶対に公開しないよう注意してください。

### ngrok と webhook の設定について

支払いテストの実行のため、ngrokを利用します。
※あくまで開発専用で、本番環境では使わないようにしてください。

#### 事前準備：ngrokのインストール

1. [ngrok公式サイト](https://ngrok.com/)にアクセスし、無料アカウントを作成してください。
2. 公式サイトの「Setup & Installation」セクションに従い、OSに合った方法でngrokをインストールしてください。
（Homebrew、またはzipファイルを直接ダウンロードして展開 など）
※zipファイルからインストールした場合は、必要に応じてngrokの実行ファイルがあるフォルダをPATHに追加してください。  
4. 公式サイトに従い、ターミナルで以下コマンドを実行し、ngrokにアカウントの認証情報（Authtoken）を設定します。

```bash
ngrok config add-authtoken YOUR_AUTHTOKEN
```
※Authtoken（上記でいうYOUR_AUTHTOKEN）はngrok公式サイトの「Setup & Installation」セクションで確認できます。

#### ngrokトンネルの起動手順
1. 以下のコマンドでローカルのポート80番を公開します。

```bash
ngrok http 80
```

2. 出力されるURLをコピーしてください。

```nginx
Forwarding  https://3092-xx-xx-xx.ngrok-free.app -> http://localhost:80
```

※上記の`https://3092-xx-xx-xx.ngrok-free.app`の部分をコピーします。

3. Stripeダッシュボードの左サイドバーから 「開発者」 > 「Webhook」 にアクセスし、「+ エンドポイントを追加」をクリックします。

4. 「受信するイベントの選択」で以下の3つを選択して「続行」を押下します。
- checkout.session.async_payment_failed
- checkout.session.async_payment_succeeded
- checkout.session.completed

5. 「エンドポイントの設定」画面で、送信先タイプに 「Webhookエンドポイント」 を選択して「続行」を押下します。

6. エンドポイントURLには、手順2でコピーしたURLの末尾に `/webhook/stripe` を付けて入力します。
例）`https://xxxx.ngrok-free.app/webhook/stripe`
※「送信先名」などの項目は任意で入力してください。
以上で送信先の設定が完了します。

7. テストが終わったら、Ctrl+Cなどでトンネルを停止してください。
※次回再度使用する場合は ngrok http 80 を実行して、新しいURLを取得しなおしてください。

### Stripe CLI 設定について

#### 1. Stripe CLI のログイン
```bash
docker run --rm -it -v ~/.config/stripe:/root/.config/stripe stripe/stripe-cli login
```
- 出力されたURLにアクセスし、認証を完了してください。

#### 2. Webhook署名キーの確認
```bash
docker-compose logs stripe-cli
```
- 以下のようなログが表示され、Webhook署名キー（whsec_xxx...）が確認できます：
```bash
stripe-cli  | Ready! You are using Stripe API Version [2025-04-30.basil]. 
stripe-cli  | Your webhook signing secret is whsec_xxxxxxxxxxxxxxxxxxxxx (^C to quit)
```

#### 3. .envへの設定
プロジェクトの.envに以下を追加。
```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
```

#### 4. Stripe CLI 設定ファイルの調整（任意）

- CLIの設定ファイルは `~/.config/stripe/config.toml` にあります。
- `test_mode_api_key` は `.env` の `STRIPE_SECRET` と同じ値にしてください。
##### 編集できない場合は、権限を確認し、必要に応じて以下のコマンドで権限を付与してください。
```bash
sudo chmod u+w ~/.config/stripe/config.toml
nano ~/.config/stripe/config.toml
```
編集後はStripe CLIのコンテナを再起動してください。
```bash
docker-compose restart stripe-cli
```

#### 注意：Webhook署名エラーについて
- `.env` の `STRIPE_SECRET` と`config.toml`の`test_mode_api_key`は必ず一致させてください。
- 本番環境の Webhook も受信するため、`whsec` が異なることによる署名検証エラーが Stripe ダッシュボード上やログに表示される場合がありますが、無視して構いません。

### MailHog の利用について（操作不要）

- メール送信機能の動作確認はMailHogのWeb UI（ http://localhost:8025/ ）で行います。
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

## 支払いテストについて

1. Stripeでの成功テスト用カード番号は `4242 4242 4242 4242`（任意の有効期限・CVCで利用可能）です。
2. Stripeでの失敗テスト用カード番号は、`4000 0000 0000 0002`（任意の有効期限・CVCで利用可能）です。<br>
詳しくは [Stripe公式テストカード一覧](https://docs.stripe.com/testing#international-cards) をご参照ください。
3. コンビニ支払いのテストには、テスト用電話番号 `11111111110` を利用できます。3分後に決済が完了したとみなされます。
- 本番用Webhookも届くため、whsec の違いで署名エラーが出ることがあります（Stripeダッシュボードに表示されますが無視してOKです）。

## URL

- 開発環境：http://localhost/
- phpMyAdmin：http://localhost:8080/
- MailHog UI: http://localhost:8025/ <br>
※ローカル環境で送信された認証メールや通知メールを確認できます。
- Stripe Dashboard：https://dashboard.stripe.com/test <br>
※テストモードでの支払い状況やWebhookイベントの確認に使います。

## PHPUnit テストの実行方法（当プロジェクト用）

### テスト実行手順

#### 1. テスト用データベースを作成
```bash
docker-compose exec mysql bash
mysql -u root -p

#MySQLログイン後
CREATE DATABASE demo_test;
SHOW DATABASES;
```

#### 2. APP_KEY の生成
```
docker-compose exec php bash
php artisan key:generate --env=testing
php artisan config:clear

```

#### 3. Stripeの設定
`.env.testing` はあらかじめ用意してありますが、セキュリティ上の理由から、以下の Stripe 関連のキーは空欄になっています。
.env（開発環境）と同様に、各種キーを .env.testing に設定してください。

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

#### 4. テストの実行（すべてのテストを実行）
```
php artisan test tests/Feature
```

### 補足事項
- 当プロジェクトでは、テスト環境を `.env.testing` ファイルおよび `config/database.php` の `mysql_test` 接続で構築しています。
- 各Featureテストクラスで use RefreshDatabase; を使用しているため、テストごとに自動でマイグレーションが実行されます。
- Seederは各テスト内で必要なものだけを呼び出す構成です。
- phpunit.xml は編集・使用しておらず、.env.testing の設定で切り替え管理しています。
- 誤って .env の本番DBを使わないよう注意してください。

## その他
### Seederに関する補足：
- ItemsSeederにおいて、指定してあるダミーの商品データにカテゴリー情報を追加しました。
- UsersSeeder は現在 100人のユーザー を作成しますが、将来的に1,000人規模のユーザー数を想定し、テストにてそのパフォーマンス確認も実施済みです。

### 購入情報の管理について：
- purchases テーブルの completed_at カラムで、ユーザーによる 購入操作の完了時刻 を記録しています。
- 実際の決済完了（入金確認）は、Stripeの webhook連携 により受信し、paid_at カラムに記録する仕様です。

### メール認証の仕様：
- 新規ユーザー登録時のみ、確認メールを自動送信します。
- 認証前ユーザーがログインなどを試みた場合、再送信は行わず、認証を促す専用ページにリダイレクトされます。

### 表示確認テストについて：
- Chrome / Firefox：開発者のPCにインストール済みのブラウザを使用して表示確認を行いました。
- Safari：playwright ディレクトリ内で以下のコマンドを実行し、表示確認を行いました。
```bash
npm run test:safari
```

## ER図
![Case1 drawio](https://github.com/user-attachments/assets/1bcdbaa6-310f-47af-944f-19104026ab87)


## 画面例
### 商品一覧画面
![01_商品一覧画面](https://github.com/user-attachments/assets/2500ce1c-3ff4-4f92-bbe5-fd9012bc8619)

### 会員登録画面（エラーメッセージの例）
![02_会員登録画面](https://github.com/user-attachments/assets/98918394-aa50-4389-b753-774be724d5f0)

### メール認証誘導画面
![03_メール認証誘導画面](https://github.com/user-attachments/assets/60bf9d4c-873a-405f-9c87-1d790a3e44d1)

### プロフィール設定画面
![04_プロフィール設定画面](https://github.com/user-attachments/assets/cc432773-e396-4d68-8905-9ccb48596881)

### 商品出品画面
![05_商品出品画面](https://github.com/user-attachments/assets/de6447f7-04a0-464e-8d99-c1e99caa6ad3)

### プロフィール画面（出品した商品）
![06_プロフィール画面（出品した商品）](https://github.com/user-attachments/assets/0972e93d-6c90-4f54-b587-cc80e4c9cd09)

### 商品詳細画面
![07_商品詳細画面](https://github.com/user-attachments/assets/07428981-2299-49cb-9530-21453d3ce02f)

### 商品購入画面
![08_商品購入画面](https://github.com/user-attachments/assets/5c5801f1-0240-4ce3-aeef-bdb13007a0fc)

### Stripeでのカード支払い画面
![09_Stripeでのカード支払い画面](https://github.com/user-attachments/assets/b8d4387e-e351-42c3-b33e-a2d88a431da8)

### Stripe決済成功確認画面
![10_Stripe決済成功確認画面](https://github.com/user-attachments/assets/148e0895-2bfa-4a83-a919-25be465b5264)

### レスポンス確認の例（タブレット 768px）
![11_レスポンス確認の例（タブレット 768px）](https://github.com/user-attachments/assets/56c97efb-c11e-48f0-bdfb-bb69d5bfc0b9)

### Firefoxでの確認の例
![12_Firefoxでの確認の例](https://github.com/user-attachments/assets/6c48d724-df6d-4aa5-9b18-ef8cd44abcbd)

### Playwrightを用いたSafariでの確認の例
![13_Playwrightを用いたSafariでの確認の例](https://github.com/user-attachments/assets/421e3fc1-503b-4606-b67a-454f5d20d7f7)



