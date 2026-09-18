import { reactive } from 'vue'

export const cartStore = reactive({
    open: false,
    loading: false,
    loaded: false,
    items: [],
    total: 0,
    subtotal: 0,
    discountAmount: 0,
    currency: 'RUB',
    baseRole: null,
    orderRole: null,
    error: null,

    get count() {
        return this.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
    },

    apply(payload) {
        this.items = payload.items || []
        this.total = Number(payload.total || 0)
        this.subtotal = Number(payload.subtotal || 0)
        this.discountAmount = Number(payload.discount_amount || 0)
        this.currency = payload.currency || 'RUB'
        this.baseRole = payload.base_role || null
        this.orderRole = payload.order_role || null
        this.loaded = true
    },
})

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || ''
}

async function request(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            ...(options.headers || {}),
        },
        ...options,
    })

    if (response.status === 401) {
        window.dispatchEvent(new CustomEvent('open-auth-dropdown'))
        throw new Error('AUTH_REQUIRED')
    }

    const payload = await response.json().catch(() => ({}))

    if (!response.ok) {
        const firstError = Object.values(payload.errors || {})?.[0]?.[0]
        throw new Error(firstError || payload.message || 'Ошибка корзины')
    }

    return payload
}

export async function loadCart() {
    cartStore.loading = true
    cartStore.error = null

    try {
        cartStore.apply(await request('/api/cart'))
    } catch (error) {
        if (error.message !== 'AUTH_REQUIRED') cartStore.error = error.message
    } finally {
        cartStore.loading = false
    }
}

export async function addToCart(variantId, quantity = 1) {
    cartStore.loading = true
    cartStore.error = null

    try {
        const payload = await request('/api/cart/items', {
            method: 'POST',
            body: JSON.stringify({ variant_id: variantId, quantity }),
        })

        cartStore.apply(payload)
        cartStore.open = true
        return true
    } catch (error) {
        if (error.message !== 'AUTH_REQUIRED') cartStore.error = error.message
        return false
    } finally {
        cartStore.loading = false
    }
}

export async function updateCartItem(itemId, quantity) {
    cartStore.loading = true
    cartStore.apply(await request(`/api/cart/items/${itemId}`, {
        method: 'PATCH',
        body: JSON.stringify({ quantity }),
    }))
    cartStore.loading = false
}

export async function removeCartItem(itemId) {
    cartStore.loading = true
    cartStore.apply(await request(`/api/cart/items/${itemId}`, {
        method: 'DELETE',
    }))
    cartStore.loading = false
}
