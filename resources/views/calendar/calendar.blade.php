@extends('uikittemplate::app')

@section('content')
	<div id="order-calendar"></div>

	<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/it.global.min.js"></script>
	<script>

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('order-calendar');

            let isDragging = false;

            const now = new Date();
            const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: lastMonth, // 👈 mese scorso
                height: 'auto',
				locale: 'it',
                firstDay: 1,
                selectable: true,
                selectMirror: true,
                slotDuration: '00:30:00',

                events: {
                    url: '{{ $actionUrl }}',
                    method: 'GET',
                    failure: function () {
                        UIkit.notification({
                            message: 'Error loading calendar events',
                            status: 'danger'
                        });
                    }
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                select: function(info) {

                    if (!isDragging) {
                        calendar.unselect();
                        return;
                    }

                    const startInput = document.getElementById('event-start');
                    const endInput   = document.getElementById('event-end');

                    const formatDateTime = (date) => date.toISOString().slice(0, 16);

                    if (startInput) startInput.value = formatDateTime(info.start);
                    if (endInput)   endInput.value   = formatDateTime(info.end);

                    UIkit.modal('#calendar-new-event-modal').show();
                    calendar.unselect();
                },
				eventDidMount: function () {
                    document
                        .querySelectorAll('.fc-daygrid-day-number')
                        .forEach(function (el) {

                            if (el.dataset.bound) {
                                return;
                            }

                            el.dataset.bound = true;

                            el.addEventListener('click', function (e) {
                                e.preventDefault();
                                e.stopPropagation();

                                const dayCell = el.closest('.fc-daygrid-day');
                                const dateStr = dayCell.getAttribute('data-date');

                                calendar.changeView('timeGridDay', dateStr);
                            });
                        });
                },
				eventClick: function(info) {
                    // Evita la navigazione standard del link di FullCalendar
                    info.jsEvent.preventDefault();

                    // Usa l'URL fornito dall'evento (ritornato dal JSON)
                    let url = info.event.url;
                    if (!url) {
                        return;
                    }

                    // Aggiunge il parametro ?iframed=true (o &iframed=true se ci sono già query params)
                    url += (url.includes('?') ? '&' : '?') + 'iframed=true';

                    // Apri il contenuto in una lightbox UIkit come iframe
                    UIkit.lightboxPanel({
                        items: [{
                            source: url,
                            type: 'iframe'
                        }]
                    }).show();
                }            });

            calendar.render();

            calendarEl.addEventListener('pointerdown', () => {
                isDragging = true;
            });

            calendarEl.addEventListener('pointerup', () => {
                setTimeout(() => isDragging = false, 0);
            });
            // Gestione pulsanti del modal "nuovo evento":
            // il div con i pulsanti è stato trasformato in un form,
            // e qui impostiamo dinamicamente la action usando il data-url del pulsante cliccato.
            const newEventForm = document.getElementById('calendar-new-event-form');
            if (newEventForm) {
                newEventForm.querySelectorAll('button[data-url]').forEach(button => {
                    button.addEventListener('click', function (e) {
                        e.preventDefault();

                        const url = this.dataset.url;
                        if (!url) {
                            return;
                        }

                        newEventForm.setAttribute('action', url);
                        newEventForm.submit();
                    });
                });
            }
        });
	</script>


	<div id="calendar-modal" uk-modal>
		<div class="uk-modal-dialog uk-modal-body uk-width-1-1 uk-width-2-3@m">
			<button class="uk-modal-close" type="button"></button>
			<div class="calendar-modal-content">
				<div uk-spinner></div>
			</div>
		</div>
	</div>



	<div id="calendar-new-event-modal" uk-modal>
		<div class="uk-modal-dialog uk-modal-body uk-width-1-1 uk-width-2-3@m">
			<button class="uk-modal-close" type="button"></button>
            <form id="calendar-new-event-form" method="POST">
    			<div class="calendar-new-event-modal-content">
    				<h3 class="uk-modal-title">Crea evento</h3>

    				<div class="uk-margin">
                        <div><strong>Nome:</strong>
                            <input type="text" id="name" name="name">
                        </div>
                        <div><strong>Inizio:</strong>
                            <input name="starts_at" type="datetime-local" id="event-start">
                        </div>
    					<div><strong>Fine:</strong>
    						<input name="ends_at" type="datetime-local" id="event-end">
    					</div>
    				</div>

    				<hr>

					<div class="uk-grid-small uk-child-width-1-2@m" uk-grid>

						<button type="submit" class="uk-button uk-button-default uk-width-1-1"
                            data-url="{{ app('products')->route('quotations.store') }}"
                        >
                        Preventivo
						</button>

						<button type="submit" class="uk-button uk-button-primary uk-width-1-1"
                            data-url="{{ app('products')->route('orders.store') }}"
                        >
                        Ordine
						</button>

						<button type="submit" class="uk-button uk-button-secondary uk-width-1-1"
                            data-url=""
                        >
                        Appuntamento
						</button>

						<button type="submit" class="uk-button uk-button-danger uk-width-1-1"
                            data-url=""
                        >
                        Scadenza
						</button>

					</div>

    			</div>
            </form>
		</div>


@endsection
