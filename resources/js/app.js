import { createApp } from 'vue'
import RadnikUnosi from './components/radnik/Unosi.vue'
import RadnikNoviUnos from './components/radnik/NoviUnos.vue'
import VodjaProjekat from './components/vodja/Projekti.vue'
import VodjaResursi from './components/vodja/Resursi.vue'
import VodjaIzvjestaj from './components/vodja/Izvjestaj.vue'
import VodjaServis from './components/vodja/ServisniNalozi.vue'
import VodjaSviServis from './components/vodja/SviServisniNalozi.vue'
import MpmPortal from './components/mpm/Portal.vue'
import MpmProjekat from './components/mpm/Projekti.vue'
import MpmNoviProjekat from './components/mpm/NoviProjekat.vue'
import MpmRadnici from './components/mpm/Radnici.vue'
import MpmOprema from './components/mpm/Oprema.vue'
import MpmOdobrenja from './components/mpm/Odobrenja.vue'
import MpmPlan from './components/mpm/Plan.vue'
import NotificationBell from './components/shared/NotificationBell.vue'

const components = {
    'vue-radnik-unosi': RadnikUnosi,
    'vue-radnik-novi-unos': RadnikNoviUnos,
    'vue-vodja-projekti': VodjaProjekat,
    'vue-vodja-resursi': VodjaResursi,
    'vue-vodja-izvjestaj': VodjaIzvjestaj,
    'vue-vodja-servis': VodjaServis,
    'vue-vodja-svi-servisni-nalozi': VodjaSviServis,
    'vue-mpm-portal': MpmPortal,
    'vue-mpm-projekti': MpmProjekat,
    'vue-mpm-novi-projekat': MpmNoviProjekat,
    'vue-mpm-radnici': MpmRadnici,
    'vue-mpm-oprema': MpmOprema,
    'vue-mpm-odobrenja': MpmOdobrenja,
    'vue-mpm-plan': MpmPlan,
    'vue-notification-bell': NotificationBell,
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

// Re-mount after Livewire wire:navigate replaces the DOM (Flux uses this internally)
document.addEventListener('livewire:navigated', mountVueApps)
