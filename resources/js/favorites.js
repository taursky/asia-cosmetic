// Import once from resources/js/app.js: import './favorites'
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-favorite-button]')
    if (!button || button.disabled) return

    if (button.dataset.loginUrl) {
        window.location.href = button.dataset.loginUrl
        return
    }

    const id = button.dataset.productId
    const isFavorite = button.dataset.favorite === '1'
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    if (!token) return console.error('Missing CSRF token')

    button.disabled = true
    try {
        const response = await fetch(`/favorites/${encodeURIComponent(id)}`, {
            method: isFavorite ? 'DELETE' : 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
        if (response.status === 401 || response.status === 419) {
            window.location.reload()
            return
        }
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const data = await response.json()
        const active = !!data.is_favorite
        document.querySelectorAll(`[data-favorite-button][data-product-id="${id}"]`).forEach((el) => {
            el.dataset.favorite = active ? '1' : '0'
            el.setAttribute('aria-pressed', active ? 'true' : 'false')
            el.setAttribute('aria-label', active ? 'Удалить из избранного' : 'Добавить в избранное')
            el.title = active ? 'Удалить из избранного' : 'Добавить в избранное'
            el.classList.toggle('text-rose-600', active)
            el.classList.toggle('text-slate-400', !active)
            el.querySelector('svg')?.setAttribute('fill', active ? 'currentColor' : 'none')
        })
    } catch (error) {
        console.error('Не удалось обновить избранное', error)
        alert('Не удалось обновить избранное. Попробуйте ещё раз.')
    } finally {
        button.disabled = false
    }
})
