@props(['variant'])

@if($variant)
    <div
        data-vue-component="AddToCartButton"
        data-props='@json([
            "variantId" => $variant->id,
            "disabled" => ! $variant->is_active || (float) $variant->stock <= 0,
        ])'
    ></div>
@endif
