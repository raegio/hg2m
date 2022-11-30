import { Controller } from '@hotwired/stimulus';
import Modal from 'bootstrap/js/src/modal';
import fetchHtml from '../../../functions/fetch_html';

export default class extends Controller {
    static targets = [ "pageLink" ];

    pageLinkTargetConnected(element) {
        element.addEventListener('click', this.clickPage.bind(this));
    }

    _refreshSection(location, callback, params) {
        const selectors = ['.filter', '.pages', '.list', '.buttons'],
            nodes = document.querySelectorAll('#bookingSection > ' + selectors.join(', #bookingSection > '));

        fetchHtml(location, function(doc) {
            for (let i = 0; i < selectors.length; i++)
                nodes[i].parentNode.replaceChild(doc.body.querySelector('#bookingSection > ' + selectors[i]), nodes[i]);
            callback && callback(doc);
        }, params);
    }

    _refreshModal(location, callback, params) {
        const selectors = ['.title', '.alerts', '.form', '.buttons'],
            nodes = document.querySelectorAll('#bookingModal ' + selectors.join(', #bookingModal '));

        fetchHtml(location, function(doc) {
            for (let i = 0; i < selectors.length; i++)
                nodes[i].parentNode.replaceChild(doc.body.querySelector('#bookingModal ' + selectors[i]), nodes[i]);
            callback && callback(doc);
        }, params);
    }

    _getParams() {
        const form = document.forms['booking_form'],
            startDateInput = form.querySelector('#booking_form_start_date'),
            endDateInput = form.querySelector('#booking_form_end_date'),
            giteSelect = form.querySelector('#booking_form_gite'),
            tokenInput = form.querySelector('#booking_form__token'),
            params = new URLSearchParams();

        params.append(startDateInput.getAttribute('name'), startDateInput.value);
        params.append(endDateInput.getAttribute('name'), endDateInput.value);
        params.append(giteSelect.getAttribute('name'), giteSelect.value);
        params.append(tokenInput.getAttribute('name'), tokenInput.value);

        return params;
    }

    clickPage(event) {
        const location = event.target.closest('a').getAttribute('href');

        event.preventDefault();

        if (location === '#')
            return;
        
        this._refreshSection(location);
    }

    filter(event) {
        const form = document.forms['bookings_filter'],
            location = form.getAttribute('data-filter-location'),
            yearInput = form.querySelector('#bookings_filter_year'),
            statusCheckboxes = form.querySelectorAll('[id^="bookings_filter_status_"]'),
            params = new URLSearchParams();

        params.append(yearInput.getAttribute('name'), yearInput.value);
        for (const checkbox of statusCheckboxes)
            if (checkbox.checked)
                params.append(checkbox.getAttribute('name'), checkbox.value);

        this._refreshSection(location, undefined, params);
    }

    showModal(event) {
        const location = event.target.getAttribute('data-location'),
            holder = document.getElementById('bookingModal');

        fetchHtml(location, function(doc) {
            holder.parentNode.replaceChild(doc.body.firstChild, holder);

            const div = document.getElementById('bookingModal');
            this.modal = new Modal(div);

            div.addEventListener('hidden.bs.modal', event => {
                this.modal.dispose();
                div.parentNode.replaceChild(holder, div);
            });

            this.modal.show();
        }.bind(this));
    }

    loadModal(event) {
        const location = event.target.getAttribute('data-location');

        this._refreshModal(location);
    }

    submitModal(event) {
        const location = event.target.getAttribute('data-location');

        switch (event.target.getAttribute('data-submit')) {
        case 'create':
        case 'update':
            this._refreshModal(location, function(doc) {
                this._refreshSection(doc.body.firstChild.getAttribute('data-list-location'));
            }.bind(this), this._getParams());
            break;
        case 'delete':
            const form = document.forms['form'],
                methodInput = form.querySelector('input[name="_method"]'),
                tokenInput = form.querySelector('#form__token'),
                params = new URLSearchParams();

            params.append(methodInput.getAttribute('name'), methodInput.value);
            params.append(tokenInput.getAttribute('name'), tokenInput.value);

            this.modal.hide();

            this._refreshSection(location, undefined, params);
        }
    }
}
