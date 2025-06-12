@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/items/create.css') }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <h2 class="content-heading">商品の出品</h2>
        <label class="content-form-label" for="image">商品画像</label>
        <div class="image-wrapper">
            <form class="image-form" id="imageUploadForm" enctype="multipart/form-data">
                @csrf
                @if (old('sell_uploaded_image_path'))
                    <img class="uploaded-image" id="preview"
                        src="{{ asset('storage/' . old('sell_uploaded_image_path')) }}?v={{ time() }}"
                        alt="アップロード画像">
                @else
                    <img class="uploaded-image" id="preview" src="" alt="" style="display: none;">
                @endif
                <label class="image-label" for="imageInput">画像を選択する</label>
                <input class="image-input-hidden" type="file" id="imageInput" name="image">
            </form>
        </div>
        <p class="content-form-error-message image-error">
            @foreach (['image', 'sell_uploaded_image_path'] as $field)
                @error($field)
                    {{ $message }}
                @enderror
            @endforeach
        </p>

        <form class="content-form-form" action="/sell" method="post">
            @csrf
            <h3 class="item-title">商品の詳細</h3>
            <label class="content-form-label">カテゴリー</label>
            <div class="categories">
                @foreach ($categories as $category)
                    <label class="category-button">
                        <input class="category-input" type="checkbox" name="category_id[]" value="{{ $category->id }}"
                            {{ in_array($category->id, old('category_id', [])) ? 'checked' : '' }}>
                        <span class="category-text">{{ $category->name }}</span>
                    </label>
                @endforeach
            </div>
            <p class="content-form-error-message">
                @error('category_id')
                    {{ $message }}
                @enderror
            </p>

            <label class="content-form-label" for="item_condition_id">商品の状態</label>
            <select class="content-form-input content-form-select" name="item_condition_id" id="item_condition_id">
                <option value="" disabled {{ old('item_condition_id') ? '' : 'selected' }}>
                    選択してください</option>
                @foreach ($conditions as $condition)
                    <option value="{{ $condition->id }}"
                        {{ old('item_condition_id') == $condition->id ? 'selected' : '' }}>
                        {{ $condition->name }}
                    </option>
                @endforeach
            </select>
            <p class="content-form-error-message">
                @error('item_condition_id')
                    {{ $message }}
                @enderror
            </p>

            <h3 class="item-title">商品名と説明</h3>
            <label class="content-form-label" for="name">商品名</label>
            <input class="content-form-input" type="text" name="name" id="name" value="{{ old('name') }}">
            <p class="content-form-error-message">
                @error('name')
                    {{ $message }}
                @enderror
            </p>
            <label class="content-form-label" for="brand">ブランド名</label>
            <input class="content-form-input" type="text" name="brand" id="brand" value="{{ old('brand') }}">
            <p class="content-form-error-message">
                @error('brand')
                    {{ $message }}
                @enderror
            </p>

            <label class="content-form-label" for="description">商品の説明</label>
            <textarea class="content-form-textarea" name="description" id="description">{{ old('description') }}</textarea>
            <p class="content-form-error-message">
                @error('description')
                    {{ $message }}
                @enderror
            </p>

            <label class="content-form-label" for="price">販売価格</label>
            <div class="input-wrapper">
                <span class="prefix">¥</span>
                <input class="price-input" type="text" name="price" inputmode="numeric" id="price"
                    value="{{ old('price') }}">
                <script> //コンマの処理
                    const input = document.getElementById('price');
                    input.addEventListener('input', function() {
                        let value = input.value.replace(/,/g, '');
                        if (!isNaN(value) && value !== '') {
                            input.value = Number(value).toLocaleString();
                        }
                    });
                </script>
            </div>
            <p class="content-form-error-message">
                @error('price')
                    {{ $message }}
                @enderror
            </p>
            <input type="hidden" name="sell_uploaded_image_path" id="hidden_image_path"
                value="{{ old('sell_uploaded_image_path') }}">
            <input class="content-form-btn" type="submit" value="出品する">
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        // 画像アップロード非同期処理
        document.getElementById('imageInput').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            fetch('/sell/image', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const errorEl = document.querySelector('.image-error');

                    if (data.success) {
                        const preview = document.getElementById('preview');
                        preview.src = data.image_url + '?v=' + Date.now();
                        preview.style.display = 'block';

                        // hidden input に path を保存
                        const hiddenPathInput = document.getElementById('hidden_image_path');
                        if (hiddenPathInput) {
                            hiddenPathInput.value = data.path;
                        }

                        if (errorEl) {
                            errorEl.textContent = '';
                        }

                    } else if (data.errors?.image) {
                        if (errorEl) {
                            errorEl.textContent = data.errors.image.join('\n');
                        }
                    } else {
                        if (errorEl) {
                            errorEl.textContent = '画像のアップロードに失敗しました';
                        }
                    }
                });
        });
    </script>
@endsection
