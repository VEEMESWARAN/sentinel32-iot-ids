let chart;
const ctx=document.getElementById('trafficChart').getContext('2d');
chart=new Chart(ctx,{type:'line',data:{labels:[],datasets:[{label:'Packets / sec',data:[],borderColor:'#54c8ff',backgroundColor:'rgba(84,200,255,.08)',fill:true,tension:.38,borderWidth:1.5,pointRadius:0}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:450},plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(120,220,255,.04)'},ticks:{color:'#617c85',maxTicksLimit:7,font:{size:9}}},y:{beginAtZero:true,grid:{color:'rgba(120,220,255,.05)'},ticks:{color:'#617c85',font:{size:9}}}}}});

function esc(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
function toast(msg){const t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2200);}
async function loadData(){
 try{
  const r=await fetch('api/dashboard_stats.php',{cache:'no-store'});
  const d=await r.json();
  if(!d.ok)return;
  const s=d.stats,l=s.latest||{};
  document.getElementById('sensorStatus').textContent=s.online_sensors>0?'ONLINE':'OFFLINE';
  document.getElementById('sensorStatus').style.color=s.online_sensors>0?'#55e6c1':'#ff5d6c';
  document.getElementById('pps').textContent=Number(l.packets_per_second||0).toFixed(1);
  document.getElementById('devices').textContent=l.unique_devices||0;
  document.getElementById('alertsToday').textContent=s.alerts_today;
  document.getElementById('criticalToday').textContent=s.critical_today;
  document.getElementById('rssi').textContent=(l.avg_rssi??'--')+' dBm';
  document.getElementById('channel').textContent=l.channel_number??'--';
  document.getElementById('mgmt').textContent=l.management_frames??0;
  document.getElementById('deauth').textContent=l.deauth_frames??0;
  chart.data.labels=d.chart.map(x=>x.label);chart.data.datasets[0].data=d.chart.map(x=>Number(x.pps));chart.update();
  const tbody=document.getElementById('alertRows');
  tbody.innerHTML=d.alerts.length?d.alerts.map(a=>{
   const src=a.source_ip||a.source_mac||'Unknown',cl=String(a.threat_level).toLowerCase();
   return `<tr><td>${esc(a.created_at)}</td><td><code>${esc(src)}</code></td><td>${esc(a.attack_type)}</td><td><span class="badge ${cl}">${esc(a.threat_level)}</span></td><td>${Number(a.packets_per_second||0).toFixed(1)}</td><td>${esc(a.status)}</td><td><button class="action" onclick="setStatus(${Number(a.id)},'ACKNOWLEDGED')">ACK</button></td></tr>`
  }).join(''):'<tr><td colspan="7" class="empty">No intrusion alerts recorded.</td></tr>';
 }catch(e){console.error(e);}
}
async function setStatus(id,status){
 const r=await fetch('api/acknowledge.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,status})});
 const d=await r.json(); if(d.ok){toast('Incident acknowledged');loadData();}
}
function updateClock(){const d=new Date();document.getElementById('clock').textContent=d.toLocaleTimeString();document.getElementById('date').textContent=d.toLocaleDateString(undefined,{weekday:'short',day:'2-digit',month:'short',year:'numeric'});}
updateClock();setInterval(updateClock,1000);loadData();setInterval(loadData,3000);
