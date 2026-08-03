window.opnCentralInventoryOverview = function(options){
    const list = document.getElementById(options.listId);
    const summary = document.getElementById(options.summaryId);
    const errorBox = document.getElementById(options.errorId);
    const refresh = document.getElementById(options.refreshId);

    function escapeHtml(value){
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    }

    function detailValue(value){
        const text = String(value ?? '').trim();
        return text === '' ? '—' : escapeHtml(text);
    }

    function statusBadge(item){
        if(!item.managed){
            return '<span class="badge unmanaged">Unmanaged</span>';
        }

        const status = String(item.last_status || 'unknown');
        const cssClass = status === 'synchronized'
            ? 'good'
            : (
                status === 'unreachable' ||
                status === 'error'
                    ? 'bad'
                    : 'warning-status'
            );

        return '<span class="badge ' + cssClass + '">' +
            escapeHtml(
                status.charAt(0).toUpperCase() + status.slice(1)
            ) +
        '</span>';
    }

    function aliasRows(items){
        if(!items.length){
            return '<tr><td colspan="6">No aliases found.</td></tr>';
        }

        return items.map(function(item){
            return `
                <tr>
                    <td>
                        <strong>${escapeHtml(item.name)}</strong>
                        ${
                            item.description
                                ? '<br><small>' +
                                    escapeHtml(item.description) +
                                  '</small>'
                                : ''
                        }
                    </td>
                    <td>${detailValue(item.type)}</td>
                    <td>${item.enabled
                        ? '<span class="badge good">Enabled</span>'
                        : '<span class="badge neutral">Disabled</span>'
                    }</td>
                    <td>${statusBadge(item)}</td>
                    <td>${escapeHtml(item.management_reason)}</td>
                    <td>${escapeHtml(
                        item.last_checked_at || 'Never'
                    )}</td>
                </tr>
            `;
        }).join('');
    }

    function categoryRows(items){
        if(!items.length){
            return '<tr><td colspan="6">No categories found.</td></tr>';
        }

        return items.map(function(item){
            return `
                <tr>
                    <td><strong>${escapeHtml(item.name)}</strong></td>
                    <td>${detailValue(item.color || 'Default')}</td>
                    <td>${item.automatic
                        ? '<span class="badge neutral">Automatic</span>'
                        : '<span class="badge good">Persistent</span>'
                    }</td>
                    <td>${statusBadge(item)}</td>
                    <td>${escapeHtml(item.management_reason)}</td>
                    <td>${escapeHtml(
                        item.last_checked_at || 'Never'
                    )}</td>
                </tr>
            `;
        }).join('');
    }

    function tableMarkup(items){
        const aliasMode = options.type === 'aliases';

        return `
            <div class="table-scroll management-table-wrap">
                <table class="management-table inventory-table">
                    <thead>
                        <tr>
                            ${
                                aliasMode
                                    ? `
                                        <th>Alias</th>
                                        <th>Type</th>
                                        <th>State</th>
                                    `
                                    : `
                                        <th>Category</th>
                                        <th>Color</th>
                                        <th>Mode</th>
                                    `
                            }
                            <th>Management</th>
                            <th>Reason</th>
                            <th>Last checked</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${
                            aliasMode
                                ? aliasRows(items)
                                : categoryRows(items)
                        }
                    </tbody>
                </table>
            </div>
        `;
    }

    function render(data){
        const firewalls = Array.isArray(data.firewalls)
            ? data.firewalls
            : [];
        const property = options.type;
        const okProperty = property + '_ok';
        const errorProperty = property + '_error';

        let total = 0;
        let managedTotal = 0;
        let unmanagedTotal = 0;
        let reachable = 0;

        firewalls.forEach(function(result){
            if(result[okProperty]){
                reachable += 1;
            }

            const items = Array.isArray(result[property])
                ? result[property]
                : [];

            total += items.length;
            managedTotal += items.filter(
                item => item.managed
            ).length;
            unmanagedTotal += items.filter(
                item => !item.managed
            ).length;
        });

        summary.innerHTML =
            firewalls.length + ' firewalls · ' +
            reachable + ' reachable · ' +
            total + ' total · ' +
            '<span class="badge good">' +
                managedTotal + ' managed</span> ' +
            '<span class="badge unmanaged">' +
                unmanagedTotal + ' unmanaged</span>';

        list.innerHTML = firewalls.length
            ? firewalls.map(function(result){
                const items = Array.isArray(result[property])
                    ? result[property]
                    : [];
                const managed = items.filter(
                    item => item.managed
                ).length;
                const unmanaged = items.length - managed;
                const available = result[okProperty];

                return `
                    <section class="card vpn-summary-card">
                        <div class="vpn-summary-main">
                            <div class="vpn-summary-identity">
                                <h2>${escapeHtml(
                                    result.firewall.name
                                )}</h2>
                                <a
                                    class="muted"
                                    href="${escapeHtml(
                                        result.firewall.base_url
                                    )}"
                                    target="_blank"
                                    rel="noopener"
                                >${escapeHtml(
                                    result.firewall.base_url
                                )}</a>
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">
                                    ${escapeHtml(options.label)}
                                </span>
                                ${
                                    available
                                        ? '<span class="badge neutral">' +
                                            items.length +
                                          '</span>'
                                        : '<span class="badge bad">' +
                                            'Unavailable</span>'
                                }
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">
                                    Summary
                                </span>
                                ${
                                    available
                                        ? '<span class="muted">' +
                                            managed + ' managed · ' +
                                            unmanaged + ' unmanaged' +
                                          '</span>'
                                        : '<span class="muted">' +
                                            escapeHtml(
                                                result[errorProperty]
                                            ) +
                                          '</span>'
                                }
                            </div>

                            <div class="vpn-summary-actions">
                                <button
                                    type="button"
                                    class="button secondary vpn-details-toggle"
                                    aria-expanded="false"
                                >
                                    Details
                                </button>
                            </div>
                        </div>

                        <div class="vpn-details-panel" hidden>
                            <div class="vpn-details-header">
                                <div>
                                    <strong>
                                        All ${escapeHtml(
                                            options.label.toLowerCase()
                                        )}
                                    </strong>
                                    <div class="muted">
                                        ${escapeHtml(
                                            result.firewall.name
                                        )}
                                    </div>
                                </div>

                                <div class="management-row-actions">
                                    <a
                                        class="button"
                                        href="${escapeHtml(
                                            options.addUrl
                                        )}?firewall_id=${
                                            result.firewall.id
                                        }"
                                    >
                                        Add to this OPNsense
                                    </a>
                                    <a
                                        class="button secondary"
                                        href="${escapeHtml(
                                            options.addUrl
                                        )}?scope=all"
                                    >
                                        Add to all OPNsense
                                    </a>
                                </div>
                            </div>

                            ${
                                available
                                    ? tableMarkup(items)
                                    : '<div class="alert error ' +
                                        'inventory-error">' +
                                        escapeHtml(
                                            result[errorProperty]
                                        ) +
                                      '</div>'
                            }
                        </div>
                    </section>
                `;
            }).join('')
            : '<section class="card vpn-summary-card">' +
                '<p class="muted">No firewalls configured.</p>' +
              '</section>';

        list.querySelectorAll('.vpn-details-toggle').forEach(
            function(button){
                button.addEventListener('click', function(){
                    const card = button.closest('.vpn-summary-card');
                    const panel = card.querySelector(
                        '.vpn-details-panel'
                    );
                    const expanded =
                        button.getAttribute('aria-expanded') === 'true';

                    button.setAttribute(
                        'aria-expanded',
                        expanded ? 'false' : 'true'
                    );
                    button.textContent = expanded
                        ? 'Details'
                        : 'Hide details';
                    panel.hidden = expanded;
                    card.classList.toggle(
                        'vpn-summary-expanded',
                        !expanded
                    );
                });
            }
        );

        const failures = firewalls.filter(
            result => !result[okProperty]
        );

        if(failures.length){
            errorBox.textContent = failures.map(
                result =>
                    result.firewall.name + ': ' +
                    result[errorProperty]
            ).join(' | ');
            errorBox.classList.remove('hidden');
        }else{
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }
    }

    async function load(){
        refresh.disabled = true;
        refresh.textContent = 'Loading…';
        errorBox.classList.add('hidden');

        try{
            const response = await fetch(
                '/alias_category_inventory_data.php',
                {
                    credentials: 'same-origin',
                    cache: 'no-store'
                }
            );
            const raw = await response.text();
            let data;

            try{
                data = JSON.parse(raw);
            }catch(error){
                throw new Error(
                    'Invalid server response: ' +
                    raw.replace(/\s+/g, ' ').slice(0, 700)
                );
            }

            if(!response.ok || data.ok !== true){
                throw new Error(
                    data.error || 'Inventory load failed.'
                );
            }

            render(data);
        }catch(error){
            summary.textContent = 'Inventory unavailable';
            list.innerHTML =
                '<section class="card vpn-summary-card">' +
                    '<p class="muted">' +
                        'Could not load the remote inventory.' +
                    '</p>' +
                '</section>';
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
        }finally{
            refresh.disabled = false;
            refresh.textContent = 'Refresh';
        }
    }

    refresh.addEventListener('click', load);
    load();
};
