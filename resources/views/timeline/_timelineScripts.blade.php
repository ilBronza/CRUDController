<script type="text/javascript">

    window.timelineDefaultTitle = 'N.D.';

    window.timelineLinkIframe = function(link)
    {
        const faIcon = link.faIcon ?? 'link';
        const textString = link.text ? link.text : '';
        const titleString = link.text ? ' title="${link.text}"' : '';
        const marginClass = link.text ? ' uk-margin-left ' : '';
        const classString = "uk-button uk-button-default uk-button-small";
        const extraClasses = Array.isArray(link.htmlClasses) ? ' ' + link.htmlClasses.join(' ') : '';

        return `<div class="uk-inline ${marginClass}" uk-lightbox onclick="event.stopPropagation();" onmousedown="event.stopPropagation();" onpointerdown="event.stopPropagation();">
    <a class="${classString}${extraClasses}" data-type="iframe" href="${link.url}" ${titleString}>
        ${textString}<i class="fa fa-${faIcon}"></i>
    </a>
</div>`;
    }

    window.timelineLinkTarget = function(link, target)
    {
        const faIcon = link.faIcon ?? 'link';
        const textString = link.text ? link.text : '';
        const titleString = link.text ? ' title="${link.text}"' : '';
        const marginClass = link.text ? ' uk-margin-left ' : '';
        const classString = "uk-button uk-button-default uk-button-small";
        const extraClasses = Array.isArray(link.htmlClasses) ? ' ' + link.htmlClasses.join(' ') : '';

        const targetAttr = target ? ` target="${target}"` : '';

        return `<div class="uk-inline ${marginClass}" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();" onpointerdown="event.stopPropagation();">
    <a class="${classString}${extraClasses}" href="${link.url}" ${titleString} ${targetAttr}>
        ${textString}<i class="fa fa-${faIcon}"></i>
    </a>
</div>`;
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
            // Save state of missing title before fallback
            const hadMissingTitle = (!item.title || item.title === '');
            // Fallback title from window if item.title is missing
            if (hadMissingTitle && typeof window.timelineDefaultTitle !== 'undefined')
            {
                item.title = window.timelineDefaultTitle;
            }

            wrapper.className = 'timeline-item';

            // Apply background color if provided by backend
            if (item.style && item.style.backgroundColor)
                wrapper.style.backgroundColor = item.style.backgroundColor;

            // Apply text color if provided, otherwise compute contrast color
            if (item.style && item.style.textColor)
                wrapper.style.color = item.style.textColor;
            else if (wrapper.style.backgroundColor)
            {
                // compute readable text color based on background
                const bg = wrapper.style.backgroundColor;

                // extract rgb values
                const rgb = bg.match(/\d+/g);
                if (rgb && rgb.length >= 3)
                {
                    const r = parseInt(rgb[0], 10);
                    const g = parseInt(rgb[1], 10);
                    const b = parseInt(rgb[2], 10);

                    // perceived luminance (WCAG-ish)
                    const luminance = (0.299 * r + 0.587 * g + 0.114 * b);

                    // dark text on light bg, light text on dark bg
                    wrapper.style.color = luminance > 160 ? '#000000' : '#ffffff';
                }
            }

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

            // Add rightLinksHtml
            let rightLinksHtml = '';

            if (Array.isArray(item.rightLinks))
                rightLinksHtml = item.rightLinks.map(function(link)
                {
                    if (link.target === 'iframe')
                        return window.timelineLinkIframe(link);

                    if (link.target)
                        return window.timelineLinkTarget(link, link.target);

                    return window.timelineLinkTarget(link, false);
                }).join('');

            const tooltip = item.popupTitle ? ` uk-tooltip="${item.popupTitle}"` : '';

            wrapper.innerHTML = `
                <strong ${tooltip}>${item.title}</strong>
                ${linksHtml}
                <div class="uk-inline uk-float-right">
                    ${rightLinksHtml}
                </div>
                <small>${item.description}</small>
                ${item.content ?? ''}
            `;

            // If title was missing, mark first button as danger
            if (hadMissingTitle)
            {
                const firstButton = wrapper.querySelector('.uk-button, button');
                if (firstButton)
                    firstButton.classList.add('uk-button-danger');
            }

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
            wrapper.className = 'timeline-group-label uk-padding-small';

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
                        return `<a class="uk-button uk-button-default uk-button-small" ${href}${target}${rel}${titleAttr}${data}>${text} <i class="fa fa-${icon}"></i></a>`;
                    }

                    return `<button type="button" class="uk-button uk-button-default uk-button-small timeline-group-action" data-action="${a.action ?? 'action'}"${titleAttr}${data} onclick="event.stopPropagation();">${text} <i class="fa fa-${icon}"></i></button>`;
                }).join('');
            }

            // --- Group summary (computed from items) ---
            const groupItems = items.get().filter(item => item.group === group.id);

            const owners = new Set();
            let totalSeconds = 0;
            let firstStart = null;
            let lastEnd = null;
            let missingOperatorCount = 0;

            groupItems.forEach(item => {
                // distinct owner (future-proof: ownerId from backend)
                owners.add(item.ownerId ?? item.title ?? '__unknown__');

                if (!item.title || item.title === '')
                    missingOperatorCount++;

                const start = new Date(item.start);
                const end = new Date(item.end);

                if (!firstStart || start < firstStart)
                    firstStart = start;

                if (!lastEnd || end > lastEnd)
                    lastEnd = end;

                totalSeconds += (end - start) / 1000;
            });

            const totalHours = (totalSeconds / 3600).toFixed(2);

            const formatDate = d =>
                d ? `${d.toLocaleDateString()} ${d.toLocaleTimeString().slice(0,5)}` : '—';

            wrapper.innerHTML = `
				<div class="uk-flex uk-flex-middle uk-flex-between uk-margin-small-bottom">
					<div class="timeline-group-title">${title}</div>

					<div class="timeline-group-actions uk-grid-small uk-child-width-auto" uk-grid>
                        <button
                            type="button"
                            class="uk-button uk-button-default uk-button-small timeline-group-summary-toggle"
                            uk-toggle="target: #group-summary-${group.id}"
                            data-group-id="${group.id}"
                        >
                            <i class="fa-solid fa-toggle-on"></i>
                        </button>
						${actionsHtml}
					</div>
				</div>

                <div
                    id="group-summary-${group.id}"
                    class="timeline-group-summary uk-text-small uk-text-muted"
                    hidden
                >
                    <div><strong>Operators:</strong> ${owners.size}</div>
                    <div><strong>Total time:</strong> ${totalHours} h</div>
                    <div><strong>From:</strong> ${formatDate(firstStart)}</div>
                    <div><strong>To:</strong> ${formatDate(lastEnd)}</div>
                    <div><strong>Missing operator:</strong> ${missingOperatorCount}</div>
                </div>
`;

            // Recalculate group height after UIkit toggle animation
            const toggleBtn = wrapper.querySelector('.timeline-group-summary-toggle');

            if (toggleBtn)
            {
                toggleBtn.addEventListener('click', function () {
                    // UIkit default toggle animation ~200ms
                    setTimeout(function () {
                        if (window.timeline && typeof window.timeline.redraw === 'function') {
                            window.timeline.redraw();
                        }
                    }, 220);
                });
            }

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