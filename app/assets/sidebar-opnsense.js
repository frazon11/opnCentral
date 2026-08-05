(function(){
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const nav = sidebar?.querySelector('.side-nav');

    if(!sidebar || !nav || nav.dataset.opnSidebarReady === '1'){
        return;
    }

    nav.dataset.opnSidebarReady = '1';

    const storageCollapse = 'opncentral-sidebar-collapsed';
    const storageGroups = 'opncentral-sidebar-groups-v1';
    const icons = {
        Firewalls:'▦',
        VPN:'◉',
        Actions:'⚙',
        Settings:'≡'
    };

    let storedGroups = {};
    try{
        storedGroups = JSON.parse(localStorage.getItem(storageGroups) || '{}');
    }catch(error){
        storedGroups = {};
    }

    function saveGroups(){
        localStorage.setItem(storageGroups, JSON.stringify(storedGroups));
    }

    function setGroupState(group, open, persist){
        const button = group.querySelector('.opn-sidebar-group-toggle');
        const content = group.querySelector('.opn-sidebar-group-content');
        const key = group.dataset.groupKey;

        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        content.hidden = !open;

        if(persist){
            storedGroups[key] = open;
            saveGroups();
        }
    }

    const groupHeaders = Array.from(nav.children).filter(
        element => element.classList.contains('nav-group')
    );

    groupHeaders.forEach(function(header, index){
        const label = header.textContent.trim();
        const key = label.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        const group = document.createElement('section');
        const button = document.createElement('button');
        const content = document.createElement('div');
        const icon = document.createElement('span');
        const labelElement = document.createElement('span');
        const chevron = document.createElement('span');

        group.className = 'opn-sidebar-group';
        group.dataset.groupKey = key;

        button.type = 'button';
        button.className = 'opn-sidebar-group-toggle';
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', 'opn-sidebar-group-' + key);

        icon.className = 'opn-sidebar-group-icon';
        icon.textContent = icons[label] || '•';
        icon.setAttribute('aria-hidden', 'true');

        labelElement.className = 'opn-sidebar-group-label';
        labelElement.textContent = label;

        chevron.className = 'opn-sidebar-group-chevron';
        chevron.textContent = '›';
        chevron.setAttribute('aria-hidden', 'true');

        content.className = 'opn-sidebar-group-content';
        content.id = 'opn-sidebar-group-' + key;

        button.append(icon, labelElement, chevron);
        group.append(button, content);

        let next = header.nextElementSibling;
        while(next && !next.classList.contains('nav-group')){
            const current = next;
            next = next.nextElementSibling;
            content.appendChild(current);
        }

        header.replaceWith(group);

        const hasActive = Boolean(content.querySelector('.active'));
        const configured = Object.prototype.hasOwnProperty.call(storedGroups, key)
            ? storedGroups[key]
            : undefined;
        const open = hasActive || configured === true ||
            (configured === undefined && index === 0);

        setGroupState(group, open, false);

        button.addEventListener('click', function(){
            const isOpen = button.getAttribute('aria-expanded') === 'true';
            setGroupState(group, !isOpen, true);
        });
    });

    const collapseButton = document.createElement('button');
    collapseButton.type = 'button';
    collapseButton.className = 'sidebar-collapse-button';
    collapseButton.title = 'Collapse sidebar';
    collapseButton.setAttribute('aria-label', 'Collapse sidebar');
    collapseButton.textContent = '‹';
    sidebar.querySelector('.sidebar-brand')?.appendChild(collapseButton);

    const expandRail = document.createElement('button');
    expandRail.type = 'button';
    expandRail.className = 'sidebar-expand-rail';
    expandRail.title = 'Expand sidebar';
    expandRail.setAttribute('aria-label', 'Expand sidebar');
    expandRail.textContent = '›';
    nav.prepend(expandRail);

    function setCollapsed(collapsed){
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem(storageCollapse, collapsed ? '1' : '0');
    }

    setCollapsed(
        window.matchMedia('(min-width: 901px)').matches &&
        localStorage.getItem(storageCollapse) === '1'
    );

    collapseButton.addEventListener('click', function(){
        setCollapsed(true);
    });

    expandRail.addEventListener('click', function(){
        setCollapsed(false);
    });

    window.addEventListener('resize', function(){
        if(window.matchMedia('(max-width: 900px)').matches){
            document.body.classList.remove('sidebar-collapsed');
        }else if(localStorage.getItem(storageCollapse) === '1'){
            document.body.classList.add('sidebar-collapsed');
        }
    });
})();
