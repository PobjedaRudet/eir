import { createApp } from 'vue'
import RadnikUnosi from './components/radnik/Unosi.vue'
import RadnikNoviUnos from './components/radnik/NoviUnos.vue'
import VodjaProjekat from './components/vodja/Projekti.vue'
import VodjaNoviProjekat from './components/vodja/NoviProjekat.vue'
import VodjaGradoviUlice from './components/vodja/GradoviUlice.vue'
import VodjaResursi from './components/vodja/Resursi.vue'
import VodjaIzvjestaj from './components/vodja/Izvjestaj.vue'
import VodjaServis from './components/vodja/ServisniNalozi.vue'
import VodjaSviServis from './components/vodja/SviServisniNalozi.vue'
import VodjaTimoviRadnici from './components/vodja/TimoviRadnici.vue'
import VodjaRadnici from './components/vodja/Radnici.vue'
import VodjaTimoviKatalog from './components/vodja/TimoviKatalog.vue'
import VodjaGradiliste from './components/vodja/Gradiliste.vue'
import MpmPortal from './components/mpm/Portal.vue'
import MpmProjekat from './components/mpm/Projekti.vue'
import MpmNoviProjekat from './components/mpm/NoviProjekat.vue'
import MpmRadnici from './components/mpm/Radnici.vue'
import MpmOprema from './components/mpm/Oprema.vue'
import MpmOdobrenja from './components/mpm/Odobrenja.vue'
import MpmPlan from './components/mpm/Plan.vue'
import MpmIzvjestaj from './components/mpm/Izvjestaj.vue'
import MpmNtvKatalog from './components/mpm/NtvKatalog.vue'
import NotificationBell from './components/shared/NotificationBell.vue'
import NabavkaDashboard from './components/nabavka/Dashboard.vue'

const components = {
    'vue-radnik-unosi': RadnikUnosi,
    'vue-radnik-novi-unos': RadnikNoviUnos,
    'vue-vodja-projekti': VodjaProjekat,
    'vue-vodja-novi-projekat': VodjaNoviProjekat,
    'vue-vodja-gradovi-ulice': VodjaGradoviUlice,
    'vue-vodja-resursi': VodjaResursi,
    'vue-vodja-izvjestaj': VodjaIzvjestaj,
    'vue-vodja-servis': VodjaServis,
    'vue-vodja-svi-servisni-nalozi': VodjaSviServis,
    'vue-vodja-timovi': VodjaTimoviRadnici,
    'vue-vodja-radnici': VodjaRadnici,
    'vue-vodja-timovi-katalog': VodjaTimoviKatalog,
    'vue-vodja-gradiliste': VodjaGradiliste,
    'vue-pm-portal': MpmPortal,
    'vue-pm-projekti': MpmProjekat,
    'vue-pm-novi-projekat': MpmNoviProjekat,
    'vue-pm-radnici': MpmRadnici,
    'vue-pm-oprema': MpmOprema,
    'vue-pm-odobrenja': MpmOdobrenja,
    'vue-pm-plan': MpmPlan,
    'vue-pm-izvjestaj': MpmIzvjestaj,
    'vue-pm-ntv-katalog': MpmNtvKatalog,
    'vue-notification-bell': NotificationBell,
    'vue-nabavka-dashboard': NabavkaDashboard,
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
