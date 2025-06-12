<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        const IS_LOGGED_IN = @json(Auth::check());
    </script>
</head>

<body>
    <header class="header">
        <div class="header-wrapper">
            <h1 class="sr-only">COACHTECH</h1>
            <a class="header-logo" href="{{ url('/') }}">
                <img class="header-logo-img" src="{{ asset('storage/assets/logo.svg') }}" alt="ロゴ">
            </a>
            @if (!Route::is('login') && !Route::is('register'))
                <ul class="header-nav">
                    <li class="header-nav-item-search">
                        <form class="header-nav-search-form form-control" action="{{ route('index') }}" method="GET">
                            <input class="header-nav-search-input form-control" type="text" name="keyword"
                                placeholder="なにをお探しですか？" value="{{ request('keyword') }}">
                            <input type="hidden" name="page" value="{{ $page ?? '' }}">
                            <button class="header-nav-search-btn" type="submit">検索</button>
                        </form>
                    </li>

                    @auth
                        <li class="header-nav-item">
                            <form class="header-nav-auth-form" action="/logout" method="post">
                                @csrf
                                <button class="header-nav-auth-btn">ログアウト</button>
                            </form>
                        </li>
                    @endauth
                    @guest
                        <li class="header-nav-item">
                            <a class="header-nav-auth-btn" href="{{ route('login') }}">ログイン</a>
                        </li>
                    @endguest

                    <li class="header-nav-item">
                        <a class="header-nav-mypage" href="{{ route('mypage') }}">マイページ</a>
                    </li>
                    <li class="header-nav-item">
                        <a class="header-nav-sale" href="{{ route('sell') }}">出品</a>
                    </li>
                </ul>
            @endif
        </div>
    </header>
    <div class="content">
        @yield('content')
    </div>
    @yield('scripts')
</body>

</html>
