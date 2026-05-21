@extends('uikittemplate::app')

@section('content')
	<div id="order-calendar"></div>

	<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
	<style>
		.fc-dayGridMonth-view .fc-daygrid-event.fc-month-time-timeline {
			position: relative;
			overflow: hidden;
			background-color: {{ config('crud.calendar.monthTimeline.trackColor', '#e9ecef') }} !important;
			border-color: #dee2e6 !important;
		}

		.fc-dayGridMonth-view .fc-daygrid-event.fc-month-time-timeline .fc-event-main {
			background-color: transparent;
		}

		.fc-dayGridMonth-view .fc-daygrid-event.fc-month-time-timeline::before {
			content: '';
			position: absolute;
			top: 0;
			bottom: 0;
			left: var(--fc-event-time-left, 0%);
			width: var(--fc-event-time-width, 100%);
			background-color: var(--fc-event-time-fill, currentColor);
			opacity: var(--fc-event-time-fill-opacity, 0.35);
			z-index: 0;
			pointer-events: none;
		}

		.fc-dayGridMonth-view .fc-daygrid-event .fc-event-main {
			display: flex;
			flex-direction: row;
			align-items: center;
			gap: 0.35em;
			width: 100%;
			min-width: 0;
		}

		.fc-dayGridMonth-view .fc-daygrid-event .fc-event-time {
			flex: 0 0 {{ config('crud.calendar.monthTimeline.timeColumnWidth', '3.25rem') }};
			width: {{ config('crud.calendar.monthTimeline.timeColumnWidth', '3.25rem') }};
			min-width: {{ config('crud.calendar.monthTimeline.timeColumnWidth', '3.25rem') }};
			text-align: right;
			font-variant-numeric: tabular-nums;
			white-space: nowrap;
		}

		.fc-dayGridMonth-view .fc-daygrid-event .fc-event-title {
			flex: 1 1 auto;
			min-width: 0;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.fc-dayGridMonth-view .fc-daygrid-event.fc-month-time-timeline .fc-event-main,
		.fc-dayGridMonth-view .fc-daygrid-event.fc-month-time-timeline .fc-event-title,
		.fc-dayGridMonth-view .fc-daygrid-event.fc-month-time-timeline .fc-event-time {
			position: relative;
			z-index: 1;
		}

		#order-calendar .fc-timegrid-event .fc-event-main {
			display: flex;
			align-items: center;
			gap: 0.35em;
		}

		#order-calendar .fc-event.overnight {
			border-right: 3px solid #495057 !important;
		}
	</style>
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/it.global.min.js"></script>
	<script>

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('order-calendar');
            const monthTimelineConfig = @json(config('crud.calendar.monthTimeline'));

            let isDragging = false;

            const parseDayStart = function (dateStr) {
                const parts = dateStr.split('-').map(Number);
                return new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0, 0);
            };

            const parseDayEnd = function (dateStr) {
                const parts = dateStr.split('-').map(Number);
                return new Date(parts[0], parts[1] - 1, parts[2] + 1, 0, 0, 0, 0);
            };

            const applyMonthTimelineLayout = function (info) {
                if (info.view.type !== 'dayGridMonth') {
                    return;
                }

                const event = info.event;

                if (event.allDay || !event.start) {
                    return;
                }

                const dayCell = info.el.closest('.fc-daygrid-day');
                const dayDateStr = dayCell && dayCell.getAttribute('data-date');

                if (!dayDateStr) {
                    return;
                }

                const dayStart = parseDayStart(dayDateStr);
                const dayEnd = parseDayEnd(dayDateStr);
                const dayMs = dayEnd - dayStart;

                let segStart = new Date(event.start);
                let segEnd = event.end ? new Date(event.end) : new Date(segStart.getTime() + 60 * 60 * 1000);

                if (segStart < dayStart) {
                    segStart = dayStart;
                }

                if (segEnd > dayEnd) {
                    segEnd = dayEnd;
                }

                if (segEnd <= segStart) {
                    return;
                }

                let leftPct = ((segStart - dayStart) / dayMs) * 100;
                let widthPct = ((segEnd - segStart) / dayMs) * 100;
                const minWidth = monthTimelineConfig.minWidthPercent ?? 3;

                if (widthPct < minWidth) {
                    widthPct = minWidth;

                    if (leftPct + widthPct > 100) {
                        leftPct = 100 - widthPct;
                    }
                }

                const fillColor = event.borderColor || event.color || event.textColor || '';

                info.el.classList.add('fc-month-time-timeline');
                info.el.style.setProperty('--fc-event-time-left', leftPct + '%');
                info.el.style.setProperty('--fc-event-time-width', widthPct + '%');
                info.el.style.setProperty('--fc-event-time-fill-opacity', monthTimelineConfig.fillOpacity ?? 0.35);

                if (fillColor) {
                    info.el.style.setProperty('--fc-event-time-fill', fillColor);
                }
            };

            const normalizeCalendarStatusClass = function (status) {
                return String(status)
                    .trim()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            };

            const applyEventStatusToDot = function (info) {
                const status = info.event.extendedProps && info.event.extendedProps.status;

                if (!status) {
                    return;
                }

                const statusClass = normalizeCalendarStatusClass(status);

                if (statusClass) {
                    info.el.classList.add(statusClass);
                }

                let dot = info.el.querySelector('.fc-daygrid-event-dot');

                if (!dot && info.el.classList.contains('fc-daygrid-event')) {
                    dot = document.createElement('div');
                    dot.className = 'fc-daygrid-event-dot';

                    const fillColor = info.event.borderColor || info.event.color;

                    if (fillColor) {
                        dot.style.borderColor = fillColor;
                    }

                    const main = info.el.querySelector('.fc-event-main');

                    if (main) {
                        info.el.insertBefore(dot, main);
                    } else {
                        info.el.prepend(dot);
                    }
                }

                if (!dot) {
                    return;
                }

                if (statusClass) {
                    dot.classList.add(statusClass);
                }

                dot.setAttribute('uk-tooltip', status);
            };

            const bindDayNumberClicks = function () {
                if (calendarEl.dataset.dayNumbersBound) {
                    return;
                }

                calendarEl.dataset.dayNumbersBound = '1';

                calendarEl.addEventListener('click', function (e) {
                    const dayNumber = e.target.closest('.fc-daygrid-day-number');

                    if (!dayNumber) {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();

                    const dayCell = dayNumber.closest('.fc-daygrid-day');
                    const dateStr = dayCell.getAttribute('data-date');

                    calendar.changeView('timeGridDay', dateStr);
                });
            };

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                nextDayThreshold: @json(config('crud.calendar.nextDayThreshold')),
                height: 'auto',
				locale: 'it',
                firstDay: 1,
                selectable: true,
                selectMirror: true,
                slotDuration: '00:30:00',
                views: {
                    dayGridMonth: {
                        eventDisplay: 'block',
                        eventTimeFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }
                    }
                },

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
				eventDidMount: function (info) {
                    applyMonthTimelineLayout(info);
                    applyEventStatusToDot(info);
                    bindDayNumberClicks();
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
