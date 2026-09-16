import './bootstrap'
import { createApp } from 'vue'

// import './app.account.patch.js'
import AccountApp from './account/AccountApp.vue'
import Counter from './components/Counter.vue'
import HomeHero from './components/HomeHero.vue'

const components = {
    Counter,
    HomeHero,
    AccountApp,
}

document.querySelectorAll('[data-vue-component]').forEach((element) => {
    const name = element.dataset.vueComponent
    const component = components[name]

    if (!component) {
        console.warn(`Vue component "${name}" not found`)
        return
    }

    let props = {}

    if (element.dataset.props) {
        try {
            props = JSON.parse(element.dataset.props)
        } catch (error) {
            console.error(`Invalid Vue props for "${name}"`, error)
        }
    }

    createApp(component, props).mount(element)
})
