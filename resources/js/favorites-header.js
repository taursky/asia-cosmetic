
function updateFavoritesHeader(count) {
    const header = document.querySelector('[data-favorites-header]')

    if (!header) return

    count = Math.max(0, Number(count) || 0)

    const heart = header.querySelector('[data-favorites-heart]')
    const badge = header.querySelector('[data-favorites-badge]')

    header.dataset.count = String(count)
    header.setAttribute(
        'aria-label',
        `Избранное: ${count} товаров`
    )

    if (badge) {
        badge.hidden = count === 0
        badge.textContent = count > 99 ? '99+' : String(count)
    }

    if (heart) {
        heart.setAttribute('fill', count > 0 ? 'currentColor' : 'none')
        heart.classList.toggle('text-rose-500', count > 0)
        heart.classList.toggle('text-[#071d5d]', count === 0)
    }
}

document.addEventListener('favorites:updated', (event) => {
    updateFavoritesHeader(event.detail?.count)
})
