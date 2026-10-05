import { Controller } from '@hotwired/stimulus';

/*
 * Menu mobile repliable.
 * Sans JS, la navigation reste dépliée et le bouton caché : le site reste utilisable.
 * Avec JS, la classe "entete--js" active le repli (voir app.css, section « Navigation mobile »).
 */
export default class extends Controller {
    static targets = ['bouton', 'nav'];

    connect() {
        this.element.classList.add('entete--js');
        this.boutonTarget.hidden = false;
        this.fermerSurEchap = this.fermerSurEchap.bind(this);
        document.addEventListener('keydown', this.fermerSurEchap);
    }

    disconnect() {
        document.removeEventListener('keydown', this.fermerSurEchap);
    }

    basculer() {
        this.definirOuvert(this.boutonTarget.getAttribute('aria-expanded') !== 'true');
    }

    fermerSurEchap(evenement) {
        if (evenement.key === 'Escape' && this.boutonTarget.getAttribute('aria-expanded') === 'true') {
            this.definirOuvert(false);
            this.boutonTarget.focus();
        }
    }

    definirOuvert(ouvert) {
        this.boutonTarget.setAttribute('aria-expanded', String(ouvert));
        this.navTarget.classList.toggle('nav-principale--ouverte', ouvert);
    }
}
