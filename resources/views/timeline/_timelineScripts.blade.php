<script type="text/javascript">

    window.timelineLinkIframe = function(link)
    {
        const faIcon = link.faIcon ?? 'link';
        const textString = link.text ? link.text : '';
        const titleString = link.text ? ' title="${link.text}"' : '';
        const marginClass = link.text ? ' uk-margin-left ' : '';
        const classString = "uk-button uk-button-default uk-button-small";

        return `<div class="uk-inline ${marginClass}" uk-lightbox onclick="event.stopPropagation();" onmousedown="event.stopPropagation();" onpointerdown="event.stopPropagation();"> <a class="${classString}" data-type="iframe" href="${link.url}" ${titleString}>${textString}<i class="fa fa-${faIcon}"></i></a></div>`;
    }

    window.timelineLinkTarget = function(link, target)
    {
        const faIcon = link.faIcon ?? 'link';
        const textString = link.text ? link.text : '';
        const titleString = link.text ? ' title="${link.text}"' : '';
        const marginClass = link.text ? ' uk-margin-left ' : '';
        const classString = "uk-button uk-button-default uk-button-small";

        const targetAttr = target ? ` target="${target}"` : '';

        return `<div class="uk-inline ${marginClass}" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();" onpointerdown="event.stopPropagation();"><a class="${classString}" href="${link.url}" ${titleString} ${targetAttr}>${textString}<i class="fa fa-${faIcon}"></i></a></div>`;
    }

    window.addEventListener('sis-lightboxClosed', function() {
        window.fetchTimeline();
    });

    const API_URL = "{{ $apiEndpoint }}";

    // DOM element where the Timeline will be attached
    var container = document.getElementById('timelinecontainer');

    // Create a DataSet (allows two way data-binding)
    var items = new vis.DataSet([]);
    var groups = new vis.DataSet([]);
    var timeline = null;

    // Delegated handler for group buttons/links rendered by groupTemplate
    // Registered once to avoid duplicate listeners after repeated fetches.
    if (container) {
        container.addEventListener('click', function (e) {
            const el = e.target.closest('.timeline-group-action');
            if (!el) return;

            e.preventDefault();
            e.stopPropagation();

            const action = el.dataset.action;
            const payloadRaw = el.dataset.payload;
            let payload = null;

            try {
                payload = payloadRaw ? JSON.parse(payloadRaw) : null;
            } catch (err) {
                payload = payloadRaw;
            }

            window.dispatchEvent(new CustomEvent('timeline-group-action', {
                detail: { action, payload }
            }));
        }, true);
    }

    window.onTimelineEndResize = function (item)
    {
        fetch(API_URL, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({item: item})
        }).catch(console.error);
    };

    var options = {
        stack: true,

        locale: 'it',
        format: {
            minorLabels: {
                minute: 'HH:mm',
                hour: 'HH:mm',
            },
            majorLabels: {
                hour: 'ddd D MMM',
            }
        },

        hiddenDates: [
            {start: '2025-01-01 00:00:00', end: '2025-01-01 08:00:00', repeat: 'daily'},
            {start: '2025-01-01 20:00:00', end: '2025-01-01 24:00:00', repeat: 'daily'}
        ],

        timeAxis: {scale: 'hour', step: 4},

        template: function (item) {
            const wrapper = document.createElement('div');

            wrapper.className = 'timeline-item';

            let linksHtml = '';

            if(Array.isArray(item.links))
                linksHtml = item.links.map(function(link)
                    {
                        if(link.target === 'iframe')
                            return window.timelineLinkIframe(link);

                        if(link.target)
                            return window.timelineLinkTarget(link, link.target);

                        return window.timelineLinkTarget(link, false);

                    }).join('');

            const tooltip = item.popupTitle ? ` uk-tooltip="${item.popupTitle}"` : '';

            wrapper.innerHTML = `<strong ${tooltip}>${item.title}</strong> ${linksHtml} <small>${item.description}</small>${item.content ?? ''}`;

            // if(item.progress)
            // {
            //     const progress = document.createElement('progress');
			//
            //     progress.className = 'uk-progress';
            //     progress.value = item.progress;
            //     progress.max = 100;
			//
            //     wrapper.appendChild(progress);
            // }

            return wrapper;
        },

        groupTemplate: function(group) {
            // Allows HTML in group labels (buttons/links). Uses delegated click handler below.
            const wrapper = document.createElement('div');
            wrapper.className = 'timeline-group-label uk-flex uk-flex-between uk-padding-small';

            const title = group.content ?? group.title ?? group.label ?? '';

            // You can add per-group actions via `group.actions` (array)
            let actionsHtml = '';

            if (Array.isArray(group.actions) && group.actions.length) {
                actionsHtml = group.actions.map(a => {
                    const icon = a.faIcon ?? 'bolt';
                    const text = a.text ?? '';
                    const titleAttr = a.title ? ` title="${a.title}"` : '';
                    const data = a.payload ? ` data-payload='${JSON.stringify(a.payload).replace(/'/g, "&apos;")}'` : '';
                    const href = a.url ? ` href="${a.url}"` : '';
                    const target = a.target ? ` target="${a.target}"` : '';
                    const rel = a.target ? ' rel="noopener"' : '';

                    // If url is provided, render as link; otherwise render as button with data-action
                    if (a.url) {
                        return `<a class="uk-button uk-button-default uk-button-small timeline-group-action" data-action="${a.action ?? 'open'}"${href}${target}${rel}${titleAttr}${data} onclick="event.stopPropagation();">${text} <i class="fa fa-${icon}"></i></a>`;
                    }

                    return `<button type="button" class="uk-button uk-button-default uk-button-small timeline-group-action" data-action="${a.action ?? 'action'}"${titleAttr}${data} onclick="event.stopPropagation();">${text} <i class="fa fa-${icon}"></i></button>`;
                }).join('');
            }

            wrapper.innerHTML = `
                <div class="timeline-group-title">${title}</div>
                <div class="timeline-group-actions uk-grid-small uk-child-width-auto" uk-grid>${actionsHtml}</div>
            `;

            return wrapper;
        },

        // Enable time edits and fire when end is resized
        editable: {
            add: false,
			updateTime: true,
			updateGroup: false,
			remove: false
		},

        onMove: function (item, callback)
        {
            window.onTimelineEndResize(item);

            callback(item);
        },


    };

    async function fetchJSON(url)
    {
        const res = await fetch(url, {headers: {'Accept': 'application/json'}});
        if (!res.ok) throw new Error('HTTP ' + res.status + ' on ' + url);
        return await res.json();
    }

    function addWeekendBackgrounds(timeline, items) {

        function generateWeekends(start, end) {
            const bg = [];

            // clona le date per sicurezza
            const d = new Date(start);
            d.setHours(0,0,0,0); // 🔥 normalizza a mezzanotte

            const endDate = new Date(end);
            endDate.setHours(0,0,0,0);

            while (d < endDate) {
                const dow = d.getDay(); // 0 = domenica, 6 = sabato

                if (dow === 0 || dow === 6) {

                    const next = new Date(d);
                    next.setDate(next.getDate() + 1);
                    next.setHours(0,0,0,0); // 🔥 anche l’end deve essere mezzanotte

                    bg.push({
                        id: 'weekend-' + d.toISOString(),
                        start: new Date(d),
                        end: next,
                        type: 'background',
                        className: dow === 6 ? 'weekend-saturday' : 'weekend-sunday'
                    });
                }

                d.setDate(d.getDate() + 1);
                d.setHours(0,0,0,0); // 🔥 importantissimo
            }

            return bg;
        }

        // prima generazione
        const range = timeline.getWindow();
        items.add(generateWeekends(range.start, range.end));

        // rigenera quando l’utente fa zoom o pan
        timeline.on('rangechanged', function (props) {

            // elimina i vecchi weekend
            items.forEach(i => {
                if (i.type === 'background' && i.className?.startsWith('weekend')) {
                    items.remove(i.id);
                }
            });

            // aggiungi i nuovi
            items.add(generateWeekends(props.start, props.end));
        });
    }

    window.setTimelineData = function (data)
    {
        if (data.groups)
        {
            groups.clear();
            groups.update(data.groups);
        }

        if (data.items)
        {
            items.clear();
            items.update(data.items);
        }

        // Create the timeline only once; subsequent calls only update datasets
        if (!timeline)
        {
            timeline = new vis.Timeline(container, items, groups, options);

            addWeekendBackgrounds(timeline, items);

            timeline.on('rangechanged', function (props)
            {
                // wait a tick so vis.js has time to (re)render the axis labels
                setTimeout(function () {
                    document
                        .querySelectorAll('#timelinecontainer .vis-time-axis .vis-text.vis-major')
                        .forEach(el =>
                        {
                            const text  = el.textContent.trim().toLowerCase();
                            const label = text.split(' ')[0]; // e.g. "lun", "mar", ...

                            // add a stable class like .day-lun, .day-mar, ...
                            el.classList.add('day-' + label);
                        });
                }, 0);

                if (props && props.start && props.end) {
                    const millis = props.end - props.start;
                    const days = millis / (1000 * 60 * 60 * 24);

                    const width = container ? (container.clientWidth || container.offsetWidth || 1) : 1;
                    const daysPerPixel = days / width; // e.g. 0.02 ≈ 1 day every 50px;

                    // Zoom thresholds (daysPerPixel):
                    // - zoomed in: hours
                    // - slight zoom out: days (step 1)
                    // - more zoom out: days (weekly ticks)
                    // - zoomed out: months (abbreviated)
                    // - extreme: years
                    if (daysPerPixel > 1) {
                        // extreme zoomed out: show years
                        timeline.setOptions({
                            timeAxis: { scale: 'year', step: 1 },
                            format: {
                                minorLabels: { year: 'YY' },
                                majorLabels: { year: 'YYYY' }
                            }
                        });
                    } else if (daysPerPixel > 0.35) {
                        // zoomed out: show months (abbreviated)
                        timeline.setOptions({
                            timeAxis: { scale: 'month', step: 1 },
                            format: {
                                minorLabels: { month: 'MMM' },
                                majorLabels: { month: 'MMM YY' }
                            }
                        });
                    } else if (daysPerPixel > 0.023) {
                        // more zoomed out: show weekly ticks (keeps weekday context without clutter)
                        timeline.setOptions({
                            timeAxis: { scale: 'day', step: 7 },
                            format: {
                                minorLabels: { day: 'ddd D' },
                                majorLabels: { day: 'MMM YY' }
                            }
                        });
                    } else if (daysPerPixel > 0.012) {
                        // slight zoom out: show daily ticks (you still see weekdays)
                        timeline.setOptions({
                            timeAxis: { scale: 'day', step: 1 },
                            format: {
                                minorLabels: { day: 'ddd D' },
                                majorLabels: { day: 'MMM YY' }
                            }
                        });
                    } else {
                        // zoomed in: show hours
                        timeline.setOptions({
                            timeAxis: { scale: 'hour', step: 4 },
                            format: {
                                minorLabels: {
                                    minute: 'HH:mm',
                                    hour: 'HH:mm'
                                },
                                majorLabels: {
                                    hour: 'ddd D MMM'
                                }
                            }
                        });
                    }
                }
            });
        }
    }



    window.loadTimelineData = async function ()
    {
        return await fetchJSON(API_URL);
    }

    window.fetchTimeline = async function ()
    {
        try
        {
            // Expected shape: { groups: [...], items: [...] }

            window.setTimelineData(
                await window.loadTimelineData()
            );


        } catch (e)
        {
            window.addDangerNotification('Failed to load data:', e);
        }
    }

    window.fetchTimeline();

</script>