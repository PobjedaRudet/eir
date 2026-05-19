import { createApp } from 'vue'
import RadnikUnosi from './components/radnik/Unosi.vue'
import RadnikNoviUnos from './components/radnik/NoviUnos.vue'
import VodjaProjekat from './components/vodja/Projekti.vue'
import VodjaNoviProjekat from './components/vodja/NoviProjekat.vue'
import VodjaIzvjestaj from './components/vodja/Izvjestaj.vue'

const components = {
    'vue-radnik-unosi': RadnikUnosi,
    'vue-radnik-novi-unos': RadnikNoviUnos,
    'vue-vodja-projekti': VodjaProjekat,
    'vue-vodja-novi-projekat': VodjaNoviProjekat,
    'vue-vodja-izvjestaj': VodjaIzvjestaj,
}

function mountVueApps() {
    Object.entries(components).forEach(([id, component]) => {
        const el = document.getElementById(id)
        if (el && !el.__vue_app__) {
            createApp(component).mount(el)
        }
    })
}

// Initial mount (type="module" scripts are deferred — DOM is ready)
mountVueApps()

// Re-mount after Livewire wire:navigate replaces the DOM
document.addEventListener('livewire:navigated', mountVueApps)
