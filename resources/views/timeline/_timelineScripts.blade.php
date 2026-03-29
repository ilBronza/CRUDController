
<div id="timeline-item-modal-template" hidden>
    <div class="uk-modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <button class="uk-modal-close-default" type="button" uk-close></button>

            <div class="uk-card uk-card-small">
                <div class="uk-card-header">
                    <h2 class="uk-modal-title"></h2>
                </div>
                <div class="uk-card-body">
                    <div class="timeline-modal-content"></div>
                </div>        
                <div class="uk-card-footer">
                    <dl class="uk-column-1-3">
                        <dt>Start</dt>
                        <dd class="start"></dd>
                        <dt>End</dt>
                        <dd class="end"></dd>
                        <dt>Days</dt>
                        <dd class="days"></dd>
                    </dl>
                </div>        
            </div>
        </div>
    </div>
</div>


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
        const titleString = link.text ? ` title="${link.text}"` : '';
        const marginClass = link.text ? ' uk-margin-left ' : '';
        const classString = "uk-button uk-button-default uk-button-small";
        const extraClasses = Array.isArray(link.htmlClasses) ? ' ' + link.htmlClasses.join(' ') : '';

        const targetAttr = target ? ` target="${target}"` : '';

        return `<div class="uk-inline ${marginClass}" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();" onpointerdown="event.stopPropagation();">
    <a class="${classString}${extraClasses}" href="${link.url}" ${titleString} ${targetAttr}>
        ${textString}<i class="fa fa-${faIcon}"></i>
    </a>
</div>`;
    };

    window.timelineLinkForm = function(link)
    {
        const faIcon = link.faIcon ?? 'link';
        const textString = link.text ? link.text : '';
        const titleString = link.text ? ` title="${link.text}"` : '';
        const marginClass = link.text ? ' uk-margin-left ' : '';
        const classString = "uk-button uk-button-default uk-button-small";
        const extraClasses = Array.isArray(link.htmlClasses) ? ' ' + link.htmlClasses.join(' ') : '';
        const csrfToken = (typeof window.csrfToken !== 'undefined') ? window.csrfToken : (document.querySelector('meta[name="csrf-token"]')?.content || '');
        const method = link.method || 'POST';

        return `<form method="POST" action="${link.url}" class="uk-inline ${marginClass}" style="display:inline" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();" onpointerdown="event.stopPropagation();">
    <input type="hidden" name="_token" value="${csrfToken}">
    <input type="hidden" name="_method" value="${method}">
    <input type="hidden" name="closeIframe" value="1">
    <button type="submit" class="${classString}${extraClasses}" ${titleString}>
        ${textString}<i class="fa fa-${faIcon}"></i>
    </button>
</form>`;
    };

    window.openTimelineItemLinksModal = function(button)
    {
        const itemId = button.dataset.itemId;
        if (!itemId) return;

        const item = items.get(itemId);
        if (!item) return;

        const template = document.getElementById('timeline-item-modal-template');
        if (!template) return;

        const clone = template.firstElementChild.cloneNode(true);
        const modalId = 'timeline-modal-' + Date.now();
        clone.id = modalId;

        const modalContent = clone.querySelector('.timeline-modal-content');
        const modalTitleEl = clone.querySelector('.uk-modal-title');

        modalTitleEl.textContent = item.title ?? window.timelineDefaultTitle;

        let html = '';

        const renderLink = function(link) {
            if (link.method === 'DELETE')
                return window.timelineLinkForm(link);

            if (link.target === 'iframe')
                return window.timelineLinkIframe(link);

            if (link.target)
                return window.timelineLinkTarget(link, link.target);

            return window.timelineLinkTarget(link, false);
        };

        if (Array.isArray(item.links)) {
            html += item.links.map(renderLink).join('');
        }

        if (Array.isArray(item.rightLinks) && item.rightLinks.length) {
            html += '<div class="uk-margin-top">';
            html += item.rightLinks.map(renderLink).join('');
            html += '</div>';
        }

        if (item.description)
            html += `<div class="uk-margin-top"><small>${item.description}</small></div>`;

        if (item.content)
            html += `<div class="uk-margin-top">${item.content}</div>`;

        modalContent.innerHTML = html;

        // --- Compute precise start/end and inject into footer ---
        const startDate = item.start ? new Date(item.start) : null;
        const endDate   = item.end ? new Date(item.end) : null;

        const formatDateTime = function(d) {
            if (!d) return '—';
            const date = d.toLocaleDateString('it-IT');
            const time = d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            return date + ' ' + time;
        };

        const startEl = clone.querySelector('.start');
        const endEl   = clone.querySelector('.end');
        const daysEl  = clone.querySelector('.days');

        if (startEl) startEl.textContent = formatDateTime(startDate);
        if (endEl)   endEl.textContent   = formatDateTime(endDate);
        if (daysEl)  daysEl.textContent  = '';

        document.body.appendChild(clone);

        const modal = UIkit.modal('#' + modalId);
        modal.show();

        clone.addEventListener('hidden', function () {
            modal.$destroy(true);
            clone.remove();
        });
    };

    window.addEventListener('sis-lightboxClosed', function() {
        window.fetchTimeline();
    });

    const API_URL = "{{ $apiEndpoint }}";
    const UPDATE_URL = "{{ $timelineUpdateRoute ?? '' }}";
    const TIMELINE_ZOOM_DAYS = {{ $zoom ?? config('crud.timelineZoom', 14) }};

    // DOM element where the Timeline will be attached
    var container = document.getElementById('timelinecontainer');

    // Create a DataSet (allows two way data-binding)
    var items = new vis.DataSet([]);
    var groups = new vis.DataSet([]);
    var timeline = null;


    // --- LIVE button inside timeline-item on hover (direct binding) ---
    let liveItemTimer = null;
    let liveItemHideTimer = null;

    function attachLiveHoverHandlers()
    {
        document.querySelectorAll('#timelinecontainer .timeline-item').forEach(function(itemEl)
        {
            itemEl.addEventListener('pointerenter', function(ev)
            {
                clearTimeout(liveItemTimer);
                clearTimeout(liveItemHideTimer);

                const contentEl = itemEl.closest('.vis-item')?.querySelector('.vis-item-content');
                if (!contentEl) return;

                const rect = contentEl.getBoundingClientRect();
                const initialOffsetX = ev.clientX - rect.left;

                liveItemTimer = setTimeout(function()
                {
                    if (contentEl.querySelector('.timeline-live-inline')) return;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'uk-button uk-button-danger uk-button-small timeline-live-inline';
                    btn.innerHTML = '<i class="fa fa-link"></i>';

                    if (!itemEl.dataset.itemId) return;
                    btn.dataset.itemId = itemEl.dataset.itemId;

                    btn.style.position = 'absolute';
                    btn.style.zIndex = '30';
                    btn.style.pointerEvents = 'auto';

                    btn.onclick = function(e)
                    {
                        e.stopPropagation();
                        window.openTimelineItemLinksModal(btn);
                    };

                    btn.onmousedown = function(e){ e.stopPropagation(); };
                    btn.onpointerdown = function(e){ e.stopPropagation(); };

                    // prevent flicker when hovering the button itself
                    btn.addEventListener('pointerenter', function()
                    {
                        clearTimeout(liveItemHideTimer);
                    });

                    btn.addEventListener('pointerleave', function()
                    {
                        clearTimeout(liveItemHideTimer);

                        liveItemHideTimer = setTimeout(function()
                        {
                            if (btn)
                                btn.remove();
                        }, 700);
                    });

                    // ensure positioning context
                    if (getComputedStyle(contentEl).position === 'static')
                        contentEl.style.position = 'relative';

                    contentEl.appendChild(btn);

                    // position once based on cursor X inside vis-item-content, clamped to visible width
                    const contentWidth = contentEl.offsetWidth;
                    const btnWidth = 28; // approx width for small uk-button with icon

                    let computedLeft = initialOffsetX + 10;

                    // prevent overflow on the right
                    if (computedLeft + btnWidth > contentWidth)
                        computedLeft = contentWidth - btnWidth - 4;

                    // prevent negative overflow on the left
                    if (computedLeft < 2)
                        computedLeft = 2;

                    btn.style.left = computedLeft + 'px';
                    btn.style.top = '2px';

                }, 250);
            });
            itemEl.addEventListener('pointerleave', function(e)
            {
                const contentEl = itemEl.closest('.vis-item')?.querySelector('.vis-item-content');
                if (!contentEl) return;

                const btn = contentEl.querySelector('.timeline-live-inline');

                // if moving toward the button, do not trigger hide
                if (btn && e.relatedTarget && btn.contains(e.relatedTarget))
                    return;

                clearTimeout(liveItemHideTimer);

                liveItemHideTimer = setTimeout(function()
                {
                    const currentBtn = contentEl.querySelector('.timeline-live-inline');
                    if (currentBtn)
                        currentBtn.remove();
                }, 700);
            });
        });
    }

    // Attach after each render/update
    if (container)
    {
        const observer = new MutationObserver(function()
        {
            attachLiveHoverHandlers();
        });

        observer.observe(container, { childList: true, subtree: true });
    }

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
    fetch(UPDATE_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({_method: 'PUT', item: item})
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.success === true && data.message && typeof window.addSuccessNotification === 'function') {
            window.addSuccessNotification(data.message);
        }
    })
    .catch(console.error);
};

var options = {
    stack: true,
    showTooltips: false,

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

            // Add dynamic itemType as CSS class (if provided by backend JSON)
            if (item.itemType)
                wrapper.classList.add(item.itemType);

            wrapper.dataset.itemId = item.id;

            // --- Move style from vis-item to wrapper ---
            if (item.style)
            {
                // If style is a CSS string (default vis behavior)
                if (typeof item.style === 'string')
                {
                    wrapper.style.cssText += item.style;
                    item.style = null; // prevent vis from applying it to .vis-item
                }
                // If style is an object coming from backend
                else if (typeof item.style === 'object')
                {
                    if (item.style.backgroundColor)
                        wrapper.style.backgroundColor = item.style.backgroundColor;

                    if (item.style.textColor)
                        wrapper.style.color = item.style.textColor;
                }
            }

            // --- Fallback background color if none provided ---
            if (!wrapper.style.backgroundColor) {
                wrapper.style.backgroundColor = '#607d8b'; // default fallback color
                wrapper.style.color = '#ffffff';
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
            // Calcola finestra iniziale PRIMA di creare la timeline (evita il fit automatico di vis.js)
            var zoomDays = typeof TIMELINE_ZOOM_DAYS !== 'undefined' ? TIMELINE_ZOOM_DAYS : 14;
            var zoomMs = zoomDays * 24 * 60 * 60 * 1000;
            var itemIds = items.getIds();
            var windowStart, windowEnd;
            if (itemIds.length > 0) {
                var minStart = Infinity;
                itemIds.forEach(function(id) {
                    var it = items.get(id);
                    if (it.start) { var s = new Date(it.start).getTime(); if (s < minStart) minStart = s; }
                });
                windowStart = new Date(minStart !== Infinity ? minStart : Date.now());
            } else {
                windowStart = new Date();
            }
            windowEnd = new Date(windowStart.getTime() + zoomMs);

            var timelineOptions = Object.assign({}, options, { start: windowStart, end: windowEnd });
            timeline = new vis.Timeline(container, items, groups, timelineOptions);

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