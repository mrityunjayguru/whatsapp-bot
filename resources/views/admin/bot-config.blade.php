{{--
    Save as resources/views/admin/bot-config.blade.php

    Wrap this in your CRM's normal layout - swap the outer @extends /
    @section below for whatever your other admin pages use. The form
    itself is framework-agnostic (plain Blade + a bit of JS for the
    repeatable product rows), so it'll fit into Blade, Livewire, or a
    Vue/Inertia page shell with minor adjustment.
--}}
@extends('layout.master')

@section('content')
<div class="container py-4" style="max-width: 900px;">

    <h1 class="h4 mb-1">WhatsApp Bot - Config</h1>
    <p class="text-muted small">Changes here are live on the bot's very next customer message - no deploy or restart needed.</p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('bot-config.update') }}" id="bot-config-form">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6">Bot identity & links</h2>

                <label class="form-label small fw-bold mt-2">Company / bot name</label>
                <input type="text" name="company_name" class="form-control"
                       value="{{ old('company_name', $config['company_name']) }}">

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mt-2">Support link</label>
                        <input type="text" name="support_link" class="form-control"
                               value="{{ old('support_link', $config['support_link']) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mt-2">Demo booking link</label>
                        <input type="text" name="demo_link" class="form-control"
                               value="{{ old('demo_link', $config['demo_link']) }}">
                    </div>
                </div>

                <label class="form-label small fw-bold mt-2">Products page link</label>
                <input type="text" name="products_link" class="form-control"
                       value="{{ old('products_link', $config['products_link']) }}">
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6">Products</h2>
                <div id="products-list"></div>
                <button type="button" id="add-product-btn" class="btn btn-sm btn-outline-secondary mt-2">+ Add product</button>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6">Canned replies</h2>
                <p class="text-muted small">
                    Placeholders available: <code>{company_name}</code> <code>{support_link}</code>
                    <code>{demo_link}</code> <code>{products_link}</code>. The greeting also supports
                    <code>{name}</code> and <code>{greeting_prefix}</code>.
                </p>
                <div id="templates-list"></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save changes</button>
    </form>
</div>

<template id="product-row-template">
    <div class="border rounded p-3 mb-2 product-row">
        <button type="button" class="btn btn-sm btn-link text-danger float-end remove-product-btn">Remove</button>
        <div class="row">
            <div class="col-md-6">
                <label class="form-label small fw-bold">Display name</label>
                <input type="text" class="form-control" data-field="name">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Type</label>
                <input type="text" class="form-control" data-field="type">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label class="form-label small fw-bold mt-2">Price</label>
                <input type="text" class="form-control" data-field="price">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold mt-2">Product link</label>
                <input type="text" class="form-control" data-field="link">
            </div>
        </div>
        <label class="form-label small fw-bold mt-2">Matching key <span class="text-muted">(short id used for "price of X" matching)</span></label>
        <input type="text" class="form-control" data-field="key">
        <label class="form-label small fw-bold mt-2">Extra keywords <span class="text-muted">(comma separated)</span></label>
        <input type="text" class="form-control" data-field="keywords_text">
    </div>
</template>

<script>
// Server-rendered config, passed to JS for the repeatable product/template rows.
const initialConfig = @json($config);
const templateLabels = {
    greeting: "Greeting (when someone says hi)",
    demo: "Demo booking",
    order_status: "Order / tracking status",
    support: "Support / issue reported",
    human_handoff: "Handoff to a human agent",
    thanks: "Thanks / acknowledgement",
    fallback: "Fallback (nothing else matched)",
};

const productsList = document.getElementById('products-list');
const templatesList = document.getElementById('templates-list');
const rowTemplate = document.getElementById('product-row-template');

function addProductRow(product) {
    const node = rowTemplate.content.cloneNode(true);
    const row = node.querySelector('.product-row');
    row.querySelector('[data-field="name"]').value = product?.name ?? '';
    row.querySelector('[data-field="type"]').value = product?.type ?? '';
    row.querySelector('[data-field="price"]').value = product?.price ?? '';
    row.querySelector('[data-field="link"]').value = product?.link ?? '';
    row.querySelector('[data-field="key"]').value = product?.key ?? '';
    row.querySelector('[data-field="keywords_text"]').value = (product?.keywords ?? []).join(', ');
    row.querySelector('.remove-product-btn').addEventListener('click', () => row.remove());
    productsList.appendChild(row);
}

(initialConfig.products || []).forEach(addProductRow);
document.getElementById('add-product-btn').addEventListener('click', () => addProductRow(null));

Object.keys(templateLabels).forEach(key => {
    const wrap = document.createElement('div');
    wrap.innerHTML = `
        <label class="form-label small fw-bold mt-2">${templateLabels[key]}</label>
        <textarea class="form-control template-textarea" data-template="${key}" rows="3"></textarea>
    `;
    wrap.querySelector('textarea').value = initialConfig.templates?.[key] ?? '';
    templatesList.appendChild(wrap);
});

// On submit, gather the repeatable rows into hidden inputs Laravel's
// validator/array binding understands (products[0][name] etc.).
document.getElementById('bot-config-form').addEventListener('submit', (e) => {
    document.querySelectorAll('.product-row').forEach(row => row.remove ? null : null);

    const hiddenWrap = document.createElement('div');
    hiddenWrap.style.display = 'none';

    document.querySelectorAll('#products-list .product-row').forEach((row, i) => {
        const fields = ['name', 'type', 'price', 'link', 'key'];
        fields.forEach(f => {
            const input = document.createElement('input');
            input.name = `products[${i}][${f}]`;
            input.value = row.querySelector(`[data-field="${f}"]`).value;
            hiddenWrap.appendChild(input);
        });
        const keywords = row.querySelector('[data-field="keywords_text"]').value
            .split(',').map(s => s.trim()).filter(Boolean);
        keywords.forEach((kw, j) => {
            const input = document.createElement('input');
            input.name = `products[${i}][keywords][${j}]`;
            input.value = kw;
            hiddenWrap.appendChild(input);
        });
    });

    document.querySelectorAll('.template-textarea').forEach(ta => {
        const input = document.createElement('input');
        input.name = `templates[${ta.dataset.template}]`;
        input.value = ta.value;
        hiddenWrap.appendChild(input);
    });

    e.target.appendChild(hiddenWrap);
});
</script>
@endsection
