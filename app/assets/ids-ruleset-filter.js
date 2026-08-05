(function(){
    'use strict';

    if(location.pathname !== '/intrusion_detection.php') return;
    if((new URLSearchParams(location.search).get('view') || 'administration') !== 'download') return;

    const presets = {
        'high-confidence': [
            'botcc','botcc.portgrouped','ciarmy','compromised','drop','dshield',
            'emerging-coinminer','emerging-malware','emerging-web_client',
            'emerging-web_server','emerging-shellcode'
        ],
        'start-blocking': [
            'botcc','botcc.portgrouped','ciarmy','compromised','drop','dshield',
            'emerging-coinminer','emerging-malware'
        ],
        'avoid': [
            'emerging-activex','emerging-adware_pup','emerging-chat','emerging-dos',
            'emerging-file_sharing','emerging-games','emerging-info','emerging-misc',
            'emerging-p2p','emerging-policy','emerging-scan'
        ]
    };

    function normalize(value){
        return String(value || '')
            .toLowerCase()
            .replace(/^et[ _-]*open[\/ _-]*/,'')
            .replace(/\.rules$/,'')
            .trim();
    }

    function install(){
        const controls = document.querySelector('.ids-ruleset-presets');
        const textFilter = document.getElementById('ids-ruleset-filter');
        const rulesetSelect = document.querySelector('.ids-ruleset-select');

        if(!controls || !textFilter || !rulesetSelect) return false;
        if(document.getElementById('ids-ruleset-preset-filter')) return true;

        const presetLabel = document.createElement('label');
        presetLabel.textContent = 'Filter rulesets';

        const presetSelect = document.createElement('select');
        presetSelect.id = 'ids-ruleset-preset-filter';
        presetSelect.innerHTML =
            '<option value="all">All rulesets</option>' +
            '<option value="high-confidence">High-confidence ET</option>' +
            '<option value="start-blocking">Start-blocking set</option>' +
            '<option value="avoid">Noisy / avoid set</option>';
        presetLabel.appendChild(presetSelect);

        const textLabel = textFilter.closest('label');
        if(textLabel){
            textLabel.firstChild.textContent = 'Free-text filter';
            controls.insertBefore(presetLabel, textLabel);
        }else{
            controls.insertBefore(presetLabel, controls.firstChild);
        }

        function applyCombinedFilter(){
            const preset = presetSelect.value;
            const query = textFilter.value.trim().toLowerCase();
            const allowed = preset === 'all' ? null : new Set(presets[preset] || []);

            Array.from(rulesetSelect.options).forEach(function(option){
                const normalized = option.dataset.normalized || normalize(option.value);
                const matchesPreset = allowed === null || allowed.has(normalized);
                const matchesText = query === '' || option.textContent.toLowerCase().includes(query);
                option.hidden = !(matchesPreset && matchesText);
            });
        }

        presetSelect.addEventListener('change', applyCombinedFilter);
        textFilter.addEventListener('input', applyCombinedFilter);
        applyCombinedFilter();
        return true;
    }

    if(install()) return;

    const observer = new MutationObserver(function(){
        if(install()) observer.disconnect();
    });
    observer.observe(document.documentElement, {childList:true, subtree:true});
})();
