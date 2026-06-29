(function(){
    const POLL_INTERVAL = 3000; // ms

    async function fetchData(page, params = {}){
        const url = new URL('/admin/ajax/get_live_data.php', window.location.origin);
        url.searchParams.set('page', page);
        Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Network error');
        return res.json();
    }

    function updateDashboard(data){
        if (!data) return;
        const s = data.stats || {};
        if (s.total_teams !== undefined){ const el = document.getElementById('totalTeamsValue'); if (el) el.textContent = s.total_teams; }
        if (s.total_rounds !== undefined){ const el = document.getElementById('totalRoundsValue'); if (el) el.textContent = s.total_rounds; }
        if (s.active_round){ const el = document.getElementById('activeRoundValue'); if (el) el.textContent = s.active_round.name || 'None'; }
        if (s.total_questions !== undefined){ const el = document.getElementById('totalQuestionsValue'); if (el) el.textContent = s.total_questions; }
        if (data.top_teams){ const tbody = document.getElementById('topTeamsBody'); if (tbody){ tbody.innerHTML = data.top_teams.map(t => `
            <tr>
                <td><div class="badge-rank">${t.rank||'-'}</div></td>
                <td>${escapeHtml(t.team_name)}</td>
                <td>${escapeHtml(t.school_name||'')}</td>
                <td><strong>${t.total_marks}</strong></td>
                <td>${Number(t.percentage).toFixed(2)}%</td>
                <td><span class="badge bg-success">${t.total_correct||0}</span></td>
                <td><span class="badge bg-danger">${t.total_wrong||0}</span></td>
            </tr>`).join(''); }}
    }

    function updateLeaderboard(data){
        if (!data || !data.leaderboard) return;
        const tbody = document.getElementById('leaderboardBody');
        if (!tbody) return;
        tbody.innerHTML = data.leaderboard.map(t => `
            <tr>
                <td><div class="badge-rank">${t.rank||'-'}</div></td>
                <td>${escapeHtml(t.team_name)}</td>
                <td>${escapeHtml(t.school_name||'')}</td>
                <td><strong>${t.total_marks}</strong></td>
                <td>${Number(t.percentage).toFixed(2)}%</td>
                <td><span class="badge bg-success">${t.total_correct||0}</span></td>
                <td><span class="badge bg-danger">${t.total_wrong||0}</span></td>
            </tr>`).join('');
    }

    function updateResults(data){
        if (!data || !data.results) return;
        const tbody = document.getElementById('resultsBody');
        if (!tbody) return;
        // compute durations and fastest
        const rows = data.results;
        const durations = {};
        rows.forEach(r => {
            const start = r.start_time || r.started_at || null;
            const end = r.completed_at || null;
            if (start && end) {
                try {
                    const s = Date.parse(start.replace(' ', 'T'));
                    const e = Date.parse(end.replace(' ', 'T'));
                    if (!isNaN(s) && !isNaN(e)) {
                        let diff = (e - s) / 1000.0; // milliseconds -> seconds
                        durations[r.team_id] = diff;
                    }
                } catch (e) { /* ignore parse errors */ }
            }
        });
        const fastest = Object.keys(durations).length ? Math.min(...Object.values(durations)) : null;

        tbody.innerHTML = rows.map(r => {
            const d = durations[r.team_id] ?? null;
            const timeDisplay = d === null ? 'N/A' : Number(d).toFixed(3);
            let gapDisplay = '-';
            if (d !== null && fastest !== null) {
                const gap = d - fastest;
                if (gap > 0) {
                    gapDisplay = gap >= 1 ? ('+' + Math.floor(gap) + 's') : ('+' + gap.toFixed(3) + 's');
                }
            }
            const answered = (r.total_questions||0) - (r.skipped_answers||0);
            return `\
            <tr>\
                <td>${escapeHtml(r.team_name)}</td>\
                <td>${escapeHtml(r.round_name||'N/A')}</td>\
                <td>${timeDisplay}</td>\
                <td>${gapDisplay}</td>\
                <td>${answered}</td>\
                <td><span class="badge bg-success">${r.correct_answers||0}</span></td>\
                <td><span class="badge bg-danger">${r.wrong_answers||0}</span></td>\
                <td><strong>${r.total_marks||0}</strong></td>\
                <td>${Number(r.percentage||0).toFixed(2)}%</td>\
            </tr>`;
        }).join('');
    }

    function updateAnalytics(data){
        if (!data) return;
        if (data.stats){ const el = document.querySelectorAll('.analytics-stat'); el.forEach(node => { const key = node.getAttribute('data-key'); if (data.stats[key] !== undefined) node.textContent = data.stats[key]; }); }
        if (data.top_teams){ const tbody = document.getElementById('analyticsTopTeams'); if (tbody) tbody.innerHTML = data.top_teams.map(t => `<tr><td><div class="badge-rank">${t.rank||'-'}</div></td><td>${escapeHtml(t.team_name)}</td><td>${escapeHtml(t.school_name||'')}</td><td><strong>${t.total_marks}</strong></td><td>${Number(t.percentage).toFixed(1)}%</td></tr>`).join(''); }
        // update charts if global chart instances exist
        if (window.roundParticipationChart && data.round_participation){
            window.roundParticipationChart.data.labels = data.round_participation.map(x=>x.round_name);
            window.roundParticipationChart.data.datasets[0].data = data.round_participation.map(x=>Number(x.submissions));
            window.roundParticipationChart.update();
        }
        if (window.scoreDistributionChart && data.score_distribution){
            window.scoreDistributionChart.data.labels = Object.keys(data.score_distribution);
            window.scoreDistributionChart.data.datasets[0].data = Object.values(data.score_distribution);
            window.scoreDistributionChart.update();
        }
    }

    function escapeHtml(text){ if (text===null || text===undefined) return ''; return String(text).replace(/[&"'<>]/g, function(a){return {'&':'&amp;','"':'&quot;',"'":"&#39;","<":"&lt;",">":"&gt;"}[a];}); }

    function init(){
        const body = document.body;
        const page = body.getAttribute('data-page');
        if (!page) return;
        async function poll(){
            try{
                const params = {};
                if (page === 'results'){
                    const round = new URLSearchParams(window.location.search).get('round'); if (round) params.round = round;
                }
                const data = await fetchData(page, params);
                if (page === 'dashboard') updateDashboard(data);
                if (page === 'leaderboard') updateLeaderboard(data);
                if (page === 'analytics') updateAnalytics(data);
                if (page === 'results') updateResults(data);
            }catch(e){ console.error('live update error', e); }
            setTimeout(poll, POLL_INTERVAL);
        }
        poll();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
