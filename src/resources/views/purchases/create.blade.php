@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="/css/purchases/create.css">
@endsection

@section('content')
    <div class="content-wrapper-small">

        <div class="item-info-wrapper">
            <div class="item-main-detail">
                <img class="image-square" src="{{ asset('storage/items/' . $item->image_filename) }}"
                    alt="{{ $item->name }}">
                <div class="item-detail">
                    <h2 class="content-heading">{{ $item->name }}</h2>
                    <div class="content-price price">
                        ￥ <span class="price-num">{{ number_format($item->price) }}</span>
                    </div>
                </div>
            </div>

            {{-- 支払い方法選択フォーム --}}
            <div id="payment-form">
                <h3 class="item-title purchase-way">支払い方法</h3>
                <select class="content-form-input content-form-select form-control" name="payment_method" id="payment-method">
                    <option value="" disabled {{ session('selected_payment_method_id') ? '' : 'selected' }}>選択してください
                    </option>
                    @foreach ($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod->id }}"
                            {{ $selectedPaymentMethodId == $paymentMethod->id ? 'selected' : '' }}>
                            {{ $paymentMethod->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <p class="form-error payment-method-error">
                @error('payment_method')
                    {{ $message }}
                @enderror
            </p>

            <div class="item-title-wrapper">
                <h3 class="item-title">配送先</h3>
                <a class="content-btn change-address" href="{{ url('/purchase/address/' . $item->id) }}">変更する</a>
            </div>
            <div class="postal-code">〒 {{ $purchase->postal_code }}</div>
            <div class="address">
                {{ $purchase->address }}<br>
                {{ $purchase->building }}
            </div>
            @if (!empty($address_error))
                <p class="form-error address-group-error">
                    {{ $address_error }}
                </p>
            @endif
            <p class="form-error address-group-error">
                @error('address_group')
                    {{ $message }}
                @enderror
            </p>
        </div>

        <div class="purchase-form-wrapper">
            {{-- 購入フォーム（POST） --}}
            <form class="purchase-form" action="{{ url('/purchase/' . $item->id) }}" method="post">
                @csrf
                <table class="purchase-table">
                    <tr>
                        <th class="purchase-table-th">商品代金</th>
                        <td class="purchase-table-td price">
                            ￥ <span class="price-num">{{ number_format($item->price) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="purchase-table-th">支払い方法</th>
                        <td class="purchase-table-td purchase-method-display">
                            {{ optional($paymentMethods->firstWhere('id', $selectedPaymentMethodId))->name ?? '未選択' }}
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="postal_code" value="{{ $purchase->postal_code }}">
                <input type="hidden" name="address" value="{{ $purchase->address }}">
                <input type="hidden" name="building" value="{{ $purchase->building }}">
                <input type="hidden" name="payment_method" value="{{ $selectedPaymentMethodId }}">
                <input class="content-form-btn" type="submit" value="購入する">
            </form>
        </div>
    </div>
@endsection('content')

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentSelect = document.getElementById('payment-method');
            const itemId = @json($item->id);
            const methodDisplayTd = document.querySelector('.purchase-method-display');
            const hiddenInput = document.querySelector('input[name="payment_method"]');

            // 初期表示の即時反映（セッションに保存されているもの）
            const selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                methodDisplayTd.textContent = selectedOption.text;
                if (hiddenInput) hiddenInput.value = selectedOption.value;
            }

            paymentSelect.addEventListener('change', function() {
                const selectedValue = paymentSelect.value;
                const selectedText = paymentSelect.options[paymentSelect.selectedIndex].text;

                // 1. 画面の表示を即時更新
                if (methodDisplayTd) {
                    methodDisplayTd.textContent = selectedText;
                }

                // 2. hidden input にも反映
                if (hiddenInput) {
                    hiddenInput.value = selectedValue;
                }

                // 3. セッションに保存（非同期POST）
                const formData = new FormData();
                formData.append('payment_method', selectedValue);

                fetch(`/purchase/${itemId}/payment-method`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('通信エラー');
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            alert('支払い方法の保存に失敗しました。');
                        }
                    })
                    .catch(error => {
                        console.error('通信失敗:', error);
                    });
            });
        });
    </script>
@endsection('scripts')
