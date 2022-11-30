import { Controller } from '@hotwired/stimulus';
import fetchHtml from '../../../functions/fetch_html';

export default class extends Controller {

    static targets = [ "year", "status", "token" ];
    static values = { refreshLocation: String };

    refresh(event) {
        const form = event.target.closest('form'),
            source = event.target.closest('input, select, button').id.substring((form.getAttribute('name') + '_').length),
            params = new URLSearchParams();

        params.append(this.yearTarget.getAttribute('name'), this.yearTarget.value);
        for (const checkbox of this.statusTargets)
            if (checkbox.checked)
                params.append(checkbox.getAttribute('name'), checkbox.value);
        fetchHtml(this.refreshLocationValue + '?source=' + source, function(doc) {
            this.element.parentNode.replaceChild(doc.body.firstChild, this.element);
        }.bind(this), params);
    }
};
