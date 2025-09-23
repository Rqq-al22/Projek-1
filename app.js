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
    if (el) el.textContent = user.name;
    if (btnLogin) btnLogin.style.display = 'none';
    if (btnLogout) btnLogout.style.display = '';
  } else {
    if (el) el.textContent = '';
    if (btnLogin) btnLogin.style.display = '';
    if (btnLogout) btnLogout.style.display = 'none';
  }
}

function goto(url){ window.location.href = url; }
