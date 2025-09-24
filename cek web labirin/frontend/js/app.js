const API_BASE = '../backend';

async function api(path, options={}){
  const headers = options.headers || {};
  if (!(options.body instanceof FormData)){
    headers['Content-Type'] = 'application/json';
  }
  const res = await fetch(`${API_BASE}/${path}`, {
    credentials: 'include',
    ...options,
    headers,
  });
  return res.json();
}

async function getSession(){
  try{ return await api('auth_me.php'); }catch(e){ return { ok:false, user:null } }
}

function setLoggedUI(user){
  const el = document.querySelector('[data-user-name]');
  const btnLogin = document.querySelector('[data-btn-login]');
  const btnLogout = document.querySelector('[data-btn-logout]');
  if (user){
    if (el) el.textContent = user.name || user.username || '';
    if (btnLogin) btnLogin.style.display = 'none';
    if (btnLogout) btnLogout.style.display = '';
  } else {
    if (el) el.textContent = '';
    if (btnLogin) btnLogin.style.display = '';
    if (btnLogout) btnLogout.style.display = 'none';
  }
}

function goto(url){ window.location.href = url; }

// Testimonial slider simple navigation
document.addEventListener('DOMContentLoaded', ()=>{
  const track = document.querySelector('[data-testimonial-track]');
  if(!track) return;
  const prev = document.querySelector('.testimonial-wrap .prev');
  const next = document.querySelector('.testimonial-wrap .next');
  const step = 380; // px
  prev?.addEventListener('click', ()=> track.scrollBy({ left: -step, behavior: 'smooth' }));
  next?.addEventListener('click', ()=> track.scrollBy({ left: step, behavior: 'smooth' }));
});

// Role helpers
async function requireLoginAndRole(requiredRole){
  const r = await getSession();
  const user = r.user;
  if(!user){
    goto('./login.html');
    return Promise.reject('UNAUTH');
  }
  setLoggedUI(user);
  applyRoleVisibility(user);
  if(requiredRole && user.role !== requiredRole){
    // Jika role tidak cocok, arahkan ke halaman yang sesuai perannya
    if(user.role === 'terapis') goto('./absensi.html');
    else goto('./dashboard.html');
    return Promise.reject('FORBIDDEN');
  }
  return user;
}

function applyRoleVisibility(user){
  // Tampilkan elemen berdasarkan atribut data-role
  document.querySelectorAll('[data-role]')
    .forEach(el=>{
      const roles = (el.getAttribute('data-role')||'').split(',').map(s=>s.trim());
      el.style.display = roles.includes(user.role) ? '' : 'none';
    });
  // Nonaktifkan kontrol edit untuk orangtua (kecuali foto profil)
  if(user.role === 'orangtua'){
    document.querySelectorAll('[data-editable-parent="false"] input, [data-editable-parent="false"] select, [data-editable-parent="false"] textarea, [data-editable-parent="false"] button')
      .forEach(el=> el.disabled = true);
  }
}

// Expose globally for pages to use
window.requireLoginAndRole = requireLoginAndRole;
window.applyRoleVisibility = applyRoleVisibility;
