"""
GridBase Bills — Tenant Installer GUI
Automates full SaaS tenant provisioning on HestiaCP VPS.
"""
import tkinter as tk
from tkinter import ttk, messagebox, scrolledtext
import threading
import subprocess
import sys
import re
import secrets
import string

# Auto-install paramiko if missing
try:
    import paramiko
except ImportError:
    subprocess.check_call([sys.executable, "-m", "pip", "install", "paramiko"])
    import paramiko

# ═══════════════════════════════════════════
# VPS Configuration
# ═══════════════════════════════════════════
VPS_IP = "68.168.218.151"
VPS_USER = "root"
VPS_PASS = "4c7HVxX#"
HESTIA_USER = "grupaqgl"
HESTIA_BIN = "/usr/local/hestia/bin"
SOURCE_DOMAIN = "bills.gridbase.com.do"
SOURCE_DIR = f"/home/{HESTIA_USER}/web/{SOURCE_DOMAIN}/public_html"
GITHUB_REPO = "https://github.com/GridbaseDS/Bills.git"
DEFAULT_ADMIN_EMAIL = "soporte@gridbase.com.do"
DEFAULT_ADMIN_PASS = "SamDP_9903"
PHP_VERSION = "PHP-8_3"
PROXY_TPL = "default"
WEB_TPL = "laravel"


def generate_db_password(length=16):
    """Generate a strong random password for the database."""
    chars = string.ascii_letters + string.digits
    return ''.join(secrets.choice(chars) for _ in range(length))


class InstallerApp:
    def __init__(self, root):
        self.root = root
        self.root.title("GridBase Bills — Instalador de Empresas")
        self.root.geometry("780x720")
        self.root.resizable(True, True)
        self.root.configure(bg="#0f172a")

        self.ssh = None
        self.is_running = False

        self._build_ui()
        self.root.protocol("WM_DELETE_WINDOW", self._on_close)

    def _build_ui(self):
        is_mac = (sys.platform == "darwin")
        font_family = "Segoe UI" if sys.platform == "win32" else "Helvetica"
        
        # ── Configure styles for ttk elements ──
        style = ttk.Style()
        if not is_mac:
            style.theme_use("clam")
            style.configure("Accent.TButton",
                            font=(font_family, 11, "bold"),
                            foreground="#ffffff", background="#6366f1",
                            padding=(20, 10))
            style.map("Accent.TButton",
                      background=[("active", "#818cf8"), ("disabled", "#334155")])

            style.configure("Danger.TButton",
                            font=(font_family, 10),
                            foreground="#ffffff", background="#ef4444",
                            padding=(15, 8))
            style.map("Danger.TButton",
                      background=[("active", "#f87171"), ("disabled", "#334155")])
        else:
            style.configure("TButton", font=(font_family, 12))

        # Header
        header = tk.Frame(self.root, bg="#0f172a")
        header.pack(fill="x", padx=24, pady=(20, 4))
        
        tk.Label(header, text="GridBase Bills Installer",
                 font=(font_family, 18, "bold"),
                 foreground="#f8fafc", background="#0f172a").pack(anchor="w")
                 
        tk.Label(header, text="Provisión automática de empresas en el VPS",
                 font=(font_family, 10),
                 foreground="#94a3b8", background="#0f172a").pack(anchor="w", pady=(2, 0))

        # Separator
        sep = tk.Frame(self.root, height=1, bg="#334155")
        sep.pack(fill="x", padx=24, pady=12)

        # Input Card
        card = tk.Frame(self.root, bg="#1e293b", highlightbackground="#334155", highlightthickness=1)
        card.pack(fill="x", padx=24, pady=(0, 8))
        
        card_inner = tk.Frame(card, bg="#1e293b")
        card_inner.pack(fill="x", padx=20, pady=16)

        # Domain Input
        tk.Label(card_inner, text="Dominio de la Empresa",
                 font=(font_family, 10, "bold"),
                 foreground="#cbd5e1", background="#1e293b").grid(row=0, column=0, sticky="w", pady=(0, 4))
                 
        self.domain_var = tk.StringVar()
        self.domain_entry = tk.Entry(card_inner, textvariable=self.domain_var,
                                     font=("Consolas", 13), bg="#0f172a",
                                     fg="#f8fafc", insertbackground="#6366f1",
                                     relief="flat", bd=0, highlightthickness=1,
                                     highlightcolor="#6366f1",
                                     highlightbackground="#334155")
        self.domain_entry.grid(row=1, column=0, sticky="ew", ipady=8, padx=(0, 12))
        self.domain_entry.insert(0, "bills.empresa.com.do")
        self.domain_entry.bind("<FocusIn>", lambda e: (
            self.domain_entry.delete(0, "end")
            if self.domain_entry.get() == "bills.empresa.com.do" else None
        ))

        tk.Label(card_inner, text="Ej: bills.grupotecnomeca.com.do",
                 font=(font_family, 9),
                 foreground="#94a3b8", background="#1e293b").grid(row=2, column=0, sticky="w", pady=(2, 0))

        # Company Name Input
        tk.Label(card_inner, text="Nombre Comercial",
                 font=(font_family, 10, "bold"),
                 foreground="#cbd5e1", background="#1e293b").grid(row=0, column=1, sticky="w", pady=(0, 4))
                 
        self.company_var = tk.StringVar()
        self.company_entry = tk.Entry(card_inner, textvariable=self.company_var,
                                      font=("Consolas", 13), bg="#0f172a",
                                      fg="#f8fafc", insertbackground="#6366f1",
                                      relief="flat", bd=0, highlightthickness=1,
                                      highlightcolor="#6366f1",
                                      highlightbackground="#334155")
        self.company_entry.grid(row=1, column=1, sticky="ew", ipady=8)
        self.company_entry.insert(0, "Mi Empresa SRL")
        self.company_entry.bind("<FocusIn>", lambda e: (
            self.company_entry.delete(0, "end")
            if self.company_entry.get() == "Mi Empresa SRL" else None
        ))

        tk.Label(card_inner, text="Se muestra en el Login y Dashboard",
                 font=(font_family, 9),
                 foreground="#94a3b8", background="#1e293b").grid(row=2, column=1, sticky="w", pady=(2, 0))

        card_inner.columnconfigure(0, weight=1)
        card_inner.columnconfigure(1, weight=1)

        # ── Buttons ──
        btn_frame = tk.Frame(self.root, bg="#0f172a")
        btn_frame.pack(fill="x", padx=24, pady=(8, 4))

        if not is_mac:
            self.install_btn = ttk.Button(btn_frame, text="Instalar Empresa",
                                          style="Accent.TButton",
                                          command=self._start_install)
            self.uninstall_btn = ttk.Button(btn_frame, text="Desinstalar",
                                            style="Danger.TButton",
                                            command=self._start_uninstall)
        else:
            self.install_btn = ttk.Button(btn_frame, text="Instalar Empresa",
                                          command=self._start_install)
            self.uninstall_btn = ttk.Button(btn_frame, text="Desinstalar",
                                            command=self._start_uninstall)

        self.install_btn.pack(side="left")
        self.uninstall_btn.pack(side="left", padx=(12, 0))

        self.status_label = tk.Label(btn_frame, text="Listo", 
                                     font=(font_family, 10),
                                     foreground="#38bdf8", background="#0f172a")
        self.status_label.pack(side="right")

        # ── Progress Bar ──
        self.progress = ttk.Progressbar(self.root, mode="determinate",
                                        maximum=100, value=0)
        self.progress.pack(fill="x", padx=24, pady=(8, 4))

        # ── Log Console ──
        log_label = tk.Label(self.root, text="Registro de Actividad",
                             font=(font_family, 10, "bold"),
                             foreground="#cbd5e1", background="#0f172a")
        log_label.pack(anchor="w", padx=24, pady=(8, 2))

        self.log_text = scrolledtext.ScrolledText(
            self.root, font=("Consolas", 10), bg="#020617", fg="#e2e8f0",
            insertbackground="#6366f1", relief="flat", bd=0, wrap="word",
            highlightthickness=1, highlightbackground="#1e293b",
            height=16
        )
        self.log_text.pack(fill="both", expand=True, padx=24, pady=(0, 16))
        
        self.log_text.tag_configure("success", foreground="#4ade80")
        self.log_text.tag_configure("error", foreground="#f87171")
        self.log_text.tag_configure("info", foreground="#38bdf8")
        self.log_text.tag_configure("step", foreground="#a78bfa", font=("Consolas", 10, "bold"))
        self.log_text.tag_configure("warn", foreground="#fbbf24")

    # ═══════════════════════════════════════
    # Logging Helpers
    # ═══════════════════════════════════════
    def log(self, msg, tag="info"):
        self.log_text.insert("end", msg + "\n", tag)
        self.log_text.see("end")
        self.root.update_idletasks()

    def set_status(self, text):
        self.status_label.configure(text=text)
        self.root.update_idletasks()

    def set_progress(self, value):
        self.progress["value"] = value
        self.root.update_idletasks()

    # ═══════════════════════════════════════
    # SSH Helpers
    # ═══════════════════════════════════════
    def ssh_connect(self):
        self.log("Conectando al VPS...", "info")
        self.ssh = paramiko.SSHClient()
        self.ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        self.ssh.connect(VPS_IP, username=VPS_USER, password=VPS_PASS, timeout=15)
        self.log(f"Conectado a {VPS_IP}", "success")

    def ssh_run(self, cmd, label=None):
        if label:
            self.log(f"  → {label}", "info")
        stdin, stdout, stderr = self.ssh.exec_command(cmd, timeout=120)
        out = stdout.read().decode().strip()
        err = stderr.read().decode().strip()
        if out:
            for line in out.split("\n")[:20]:
                self.log(f"    {line}")
        if err and "warning" not in err.lower():
            for line in err.split("\n")[:10]:
                self.log(f"    [ALERTA] {line}", "warn")
        return out, err

    def ssh_close(self):
        if self.ssh:
            self.ssh.close()
            self.ssh = None

    # ═══════════════════════════════════════
    # Domain Parsing
    # ═══════════════════════════════════════
    def parse_domain(self, domain):
        """Extract a safe slug from the domain for DB naming."""
        domain = domain.strip().lower()
        # Remove protocol if present
        domain = re.sub(r'^https?://', '', domain)
        # Remove trailing slash
        domain = domain.rstrip('/')
        # Build slug: bills.grupotecnomeca.com.do → grupotecnomeca
        # Remove known TLDs to easily find the company name
        for tld in ['.com.do', '.edu.do', '.org.do', '.net.do', '.gob.do', '.do', '.com', '.net', '.org', '.io', '.co']:
            if domain.endswith(tld):
                domain_no_tld = domain[:-len(tld)]
                break
        else:
            domain_no_tld = domain
        
        parts = domain_no_tld.split('.')
        slug = parts[-1]
        # Sanitize: only alphanumeric and underscores
        slug = re.sub(r'[^a-z0-9]', '_', slug)
        return domain, slug

    # ═══════════════════════════════════════
    # INSTALL FLOW
    # ═══════════════════════════════════════
    def _start_install(self):
        if self.is_running:
            return
        domain_raw = self.domain_var.get().strip()
        company = self.company_var.get().strip()
        if not domain_raw or domain_raw == "bills.empresa.com.do":
            messagebox.showwarning("Dominio requerido",
                                   "Por favor ingresa el dominio de la empresa.")
            return
        if not company or company == "Mi Empresa SRL":
            messagebox.showwarning("Nombre requerido",
                                   "Por favor ingresa el nombre comercial de la empresa.")
            return

        self.is_running = True
        self.install_btn.configure(state="disabled")
        self.uninstall_btn.configure(state="disabled")
        self.log_text.delete("1.0", "end")
        self.set_progress(0)

        threading.Thread(target=self._install_worker,
                         args=(domain_raw, company), daemon=True).start()

    def _install_worker(self, domain_raw, company):
        try:
            domain, slug = self.parse_domain(domain_raw)
            db_name = f"{HESTIA_USER}_{slug}"
            db_user = f"{HESTIA_USER}_{slug}"
            db_pass = generate_db_password()
            app_dir = f"/home/{HESTIA_USER}/web/{domain}/public_html"

            self.log(f"═══════════════════════════════════════", "step")
            self.log(f"  INSTALANDO: {domain}", "step")
            self.log(f"  Slug: {slug}  |  DB: {db_name}", "step")
            self.log(f"═══════════════════════════════════════\n", "step")

            # Step 1: Connect
            self.set_status("Conectando al VPS...")
            self.set_progress(5)
            self.ssh_connect()

            # Step 2: Create Web Domain in HestiaCP
            self.set_status("Creando dominio web...")
            self.set_progress(10)
            self.log("\nPASO 1: Crear dominio web en HestiaCP", "step")
            out, err = self.ssh_run(
                f"{HESTIA_BIN}/v-add-web-domain {HESTIA_USER} {domain}",
                "Registrando dominio..."
            )
            if "already exists" in (out + err).lower():
                self.log("  [ALERTA] El dominio ya existe en HestiaCP. Continuando...", "warn")
            else:
                self.log(f"  [OK] Dominio {domain} creado", "success")

            # Step 2b: Set PHP version and template
            self.set_progress(15)
            self.ssh_run(
                f"{HESTIA_BIN}/v-change-web-domain-backend-tpl {HESTIA_USER} {domain} {PHP_VERSION}",
                "Configurando PHP 8.3..."
            )
            self.ssh_run(
                f"{HESTIA_BIN}/v-change-web-domain-tpl {HESTIA_USER} {domain} {WEB_TPL}",
                "Aplicando template Laravel..."
            )

            # Step 3: SSL Configuration (Direct Self-Signed SSL for Cloudflare)
            self.set_status("Generando SSL autofirmado...")
            self.set_progress(20)
            self.log("\nPASO 2: Certificado SSL Autofirmado (Optimizado para Cloudflare Full)", "step")
            
            # Generate self-signed certificate
            out_gen, err_gen = self.ssh_run(
                f"{HESTIA_BIN}/v-generate-ssl-cert {domain} admin@{domain} DO SD SD 'GridBase' IT ''",
                "Generando certificado autofirmado en el VPS..."
            )
            
            ssl_ok = False
            match = re.search(r'[Dd]irectory:\s+(\S+)', out_gen)
            if match:
                ssl_dir = match.group(1)
                self.log(f"  → Certificado temporal en {ssl_dir}", "info")
                
                # Install SSL
                out_inst, err_inst = self.ssh_run(
                    f"{HESTIA_BIN}/v-add-web-domain-ssl {HESTIA_USER} {domain} {ssl_dir}",
                    "Instalando certificado en HestiaCP..."
                )
                
                # Verify status
                out_check, _ = self.ssh_run(f"{HESTIA_BIN}/v-list-web-domain {HESTIA_USER} {domain} json")
                try:
                    import json
                    info = json.loads(out_check)
                    if info.get(domain, {}).get("SSL") == "yes":
                        ssl_ok = True
                except:
                    pass
            
            if ssl_ok:
                self.log("  SSL autofirmado configurado y activo en el VPS. ¡El túnel HTTPS con Cloudflare Full funcionará perfectamente!", "success")
            else:
                self.log("  Error crítico: No se pudo habilitar SSL en el VPS para este dominio.", "error")

            # Step 4: Create Database
            self.set_status("Creando base de datos...")
            self.set_progress(30)
            self.log(f"\nPASO 3: Crear base de datos ({db_name})", "step")
            out, err = self.ssh_run(
                f"{HESTIA_BIN}/v-add-database {HESTIA_USER} {slug} {slug} {db_pass} mysql",
                "Creando base de datos y usuario..."
            )
            if "already exists" in (out + err).lower():
                self.log("  [ALERTA] La base de datos ya existe. Obteniendo credenciales reales...", "warn")
                # Query HestiaCP for the actual DB user
                out2, _ = self.ssh_run(
                    f"{HESTIA_BIN}/v-list-databases {HESTIA_USER} json"
                )
                try:
                    import json
                    dbs = json.loads(out2)
                    real_info = dbs.get(db_name, {})
                    db_user = real_info.get("DBUSER", db_user)
                    self.log(f"  → Usuario real de DB: {db_user}", "info")
                except:
                    pass
                # Reset password to a known value
                db_pass = generate_db_password()
                self.ssh_run(
                    f"{HESTIA_BIN}/v-change-database-password {HESTIA_USER} {db_name} {db_pass}",
                    "Reseteando contraseña de la DB..."
                )
                self.log(f"  [OK] DB reutilizada: {db_name} | User: {db_user}", "success")
                self.log(f"   Nueva Password: {db_pass}", "info")
            else:
                self.log(f"  [OK] DB: {db_name} | User: {db_user}", "success")
                self.log(f"   Password: {db_pass}", "info")

            # Step 5: Clone code from source
            self.set_status("Desplegando código...")
            self.set_progress(40)
            self.log(f"\nPASO 4: Desplegar código fuente", "step")

            # Copy from the main installation
            self.ssh_run(
                f"rm -rf {app_dir}/* {app_dir}/.[!.]* 2>/dev/null; "
                f"cp -a {SOURCE_DIR}/. {app_dir}/",
                "Copiando archivos desde la instalación principal..."
            )

            # CRITICAL: Purge cached config from source installation
            self.ssh_run(
                f"rm -f {app_dir}/bootstrap/cache/config.php "
                f"{app_dir}/bootstrap/cache/routes-v7.php "
                f"{app_dir}/bootstrap/cache/packages.php "
                f"{app_dir}/bootstrap/cache/services.php "
                f"{app_dir}/.env",
                "Limpiando caché y .env del origen..."
            )

            # CRITICAL: Remove Force HTTPS from .htaccess (HestiaCP Nginx handles SSL)
            self.ssh_run(
                f"sed -i '/# Force HTTPS/,/RewriteRule.*https.*\\[L,R=301\\]/d' {app_dir}/.htaccess",
                "Eliminando Force HTTPS del .htaccess (Nginx lo maneja)..."
            )

            self.set_progress(50)
            self.log("  [OK] Código desplegado (caché purgada, HTTPS delegado a Nginx)", "success")

            # Step 6: Configure .env
            self.set_status("Configurando entorno...")
            self.set_progress(55)
            self.log(f"\nPASO 5: Configurar .env", "step")

            env_content = f'''APP_NAME="{company}"
APP_SYSTEM=bills
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://{domain}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE="{db_name}"
DB_USERNAME="{db_user}"
DB_PASSWORD='{db_pass}'

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@{domain}"
MAIL_FROM_NAME="{company}"
'''

            # Write .env via heredoc
            escaped_env = env_content.replace("'", "'\\''")
            self.ssh_run(
                f"cat > {app_dir}/.env << 'ENVEOF'\n{env_content}\nENVEOF",
                "Escribiendo archivo .env..."
            )
            self.log("  [OK] Archivo .env creado", "success")

            # Step 8: Permissions (Moved here so that user HESTIA_USER owns .env before key:generate is run)
            self.set_status("Configurando permisos...")
            self.set_progress(60)
            self.log(f"\nPASO 5b: Permisos y directorios", "step")
            self.ssh_run(
                f"chown -R {HESTIA_USER}:{HESTIA_USER} {app_dir} && "
                f"mkdir -p {app_dir}/storage/framework/cache/data "
                f"{app_dir}/storage/framework/sessions "
                f"{app_dir}/storage/framework/views "
                f"{app_dir}/storage/logs "
                f"{app_dir}/bootstrap/cache && "
                f"chown -R {HESTIA_USER}:www-data {app_dir}/storage {app_dir}/bootstrap/cache && "
                f"chmod -R 775 {app_dir}/storage {app_dir}/bootstrap/cache",
                "Aplicando permisos..."
            )
            self.log("  [OK] Permisos configurados", "success")

            # Step 7: Generate proper Laravel key (clear cache first so artisan reads .env directly)
            self.set_status("Generando clave de aplicación...")
            self.set_progress(65)
            self.ssh_run(
                f"cd {app_dir} && sudo -u {HESTIA_USER} php artisan config:clear",
                "Limpiando caché de configuración..."
            )
            self.ssh_run(
                f"cd {app_dir} && sudo -u {HESTIA_USER} php artisan key:generate --force",
                "Generando APP_KEY..."
            )

            # Step 9: Composer
            self.set_status("Instalando dependencias...")
            self.set_progress(70)
            self.log(f"\nPASO 7: Composer install", "step")
            self.ssh_run(
                f"cd {app_dir} && sudo -u {HESTIA_USER} composer install "
                f"--no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5",
                "Instalando paquetes de PHP..."
            )
            self.set_progress(80)
            self.log("  [OK] Dependencias instaladas", "success")

            # Step 10: Run Migrations & Seeders
            self.set_status("Ejecutando migraciones...")
            self.set_progress(85)
            self.log(f"\nPASO 8: Migraciones y Seeds", "step")

            # CRITICAL: clear config again before migrate to ensure correct DB
            self.ssh_run(
                f"cd {app_dir} && sudo -u {HESTIA_USER} php artisan config:clear",
                "Asegurando configuración limpia antes de migrar..."
            )

            # Verify APP_KEY was written
            out_key, _ = self.ssh_run(f"grep APP_KEY {app_dir}/.env")
            if "APP_KEY=" in out_key and "base64:" not in out_key:
                self.log("  [ALERTA] APP_KEY vacía, regenerando...", "warn")
                self.ssh_run(
                    f"cd {app_dir} && sudo -u {HESTIA_USER} php artisan key:generate --force",
                    "Re-generando APP_KEY..."
                )

            self.ssh_run(
                f"cd {app_dir} && sudo -u {HESTIA_USER} php artisan migrate --force",
                "Creando tablas..."
            )
            self.ssh_run(
                f"cd {app_dir} && sudo -u {HESTIA_USER} php artisan db:seed --force",
                "Insertando datos iniciales..."
            )
            self.log("  [OK] Base de datos inicializada", "success")

            # Step 11: Cache optimization
            self.set_status("Optimizando caché...")
            self.set_progress(92)
            self.log(f"\nPASO 9: Optimización de caché", "step")
            self.ssh_run(
                f"cd {app_dir} && sudo -u {HESTIA_USER} php artisan config:cache && "
                f"sudo -u {HESTIA_USER} php artisan route:cache && "
                f"sudo -u {HESTIA_USER} php artisan view:cache",
                "Optimizando Laravel..."
            )
            self.log("  [OK] Caché optimizada", "success")

            # ── DONE ──
            self.set_progress(100)
            self.set_status("¡Instalación completada!")
            self.log(f"\n{'═' * 50}", "success")
            self.log(f"  ¡INSTALACIÓN COMPLETADA EXITOSAMENTE!", "success")
            self.log(f"{'═' * 50}", "success")
            self.log(f"   URL:      https://{domain}", "success")
            self.log(f"   Email:    {DEFAULT_ADMIN_EMAIL}", "success")
            self.log(f"   Password: {DEFAULT_ADMIN_PASS}", "success")
            self.log(f"   DB:       {db_name}", "info")
            self.log(f"   DB Pass:  {db_pass}", "info")
            self.log(f"{'═' * 50}\n", "success")

            self.root.after(0, lambda: messagebox.showinfo(
                "[OK] Instalación Exitosa",
                f"El sistema ha sido instalado en:\n\n"
                f" https://{domain}\n\n"
                f" {DEFAULT_ADMIN_EMAIL}\n"
                f" {DEFAULT_ADMIN_PASS}\n\n"
                f"El asistente de configuración inicial aparecerá\n"
                f"en el primer inicio de sesión."
            ))

        except Exception as e:
            self.log(f"\nERROR: {str(e)}", "error")
            self.set_status("Error en la instalación")
            self.root.after(0, lambda: messagebox.showerror(
                "Error", f"La instalación falló:\n{str(e)}"))
        finally:
            self.ssh_close()
            self.is_running = False
            self.root.after(0, lambda: self.install_btn.configure(state="normal"))
            self.root.after(0, lambda: self.uninstall_btn.configure(state="normal"))

    # ═══════════════════════════════════════
    # UNINSTALL FLOW
    # ═══════════════════════════════════════
    def _start_uninstall(self):
        if self.is_running:
            return
        domain_raw = self.domain_var.get().strip()
        if not domain_raw or domain_raw == "bills.empresa.com.do":
            messagebox.showwarning("Dominio requerido",
                                   "Por favor ingresa el dominio a desinstalar.")
            return

        domain, slug = self.parse_domain(domain_raw)

        # Safety check: don't delete main domain
        if domain == SOURCE_DOMAIN:
            messagebox.showerror("¡Protegido!",
                                 "No se puede desinstalar el dominio principal del sistema.")
            return

        confirm = messagebox.askyesno(
            "Confirmar Desinstalación",
            f"¿Estás seguro de que deseas ELIMINAR completamente?\n\n"
            f" Dominio: {domain}\n"
            f" Base de datos: {HESTIA_USER}_{slug}\n\n"
            f"Esta acción NO se puede deshacer.",
            icon="warning"
        )
        if not confirm:
            return

        self.is_running = True
        self.install_btn.configure(state="disabled")
        self.uninstall_btn.configure(state="disabled")
        self.log_text.delete("1.0", "end")
        self.set_progress(0)

        threading.Thread(target=self._uninstall_worker,
                         args=(domain, slug), daemon=True).start()

    def _uninstall_worker(self, domain, slug):
        try:
            db_name = f"{HESTIA_USER}_{slug}"
            self.log(f"DESINSTALANDO: {domain}\n", "error")

            self.set_status("Conectando al VPS...")
            self.set_progress(10)
            self.ssh_connect()

            # Delete database
            self.set_status("Eliminando base de datos...")
            self.set_progress(30)
            self.log(" Eliminando base de datos...", "step")
            self.ssh_run(
                f"{HESTIA_BIN}/v-delete-database {HESTIA_USER} {db_name}",
                f"Eliminando {db_name}..."
            )
            self.log(f"  [OK] Base de datos {db_name} eliminada", "success")

            # Delete web domain (this also removes files)
            self.set_status("Eliminando dominio web...")
            self.set_progress(60)
            self.log(" Eliminando dominio web...", "step")
            self.ssh_run(
                f"{HESTIA_BIN}/v-delete-web-domain {HESTIA_USER} {domain}",
                f"Eliminando {domain}..."
            )
            self.log(f"  [OK] Dominio {domain} eliminado", "success")

            self.set_progress(100)
            self.set_status("Desinstalación completada")
            self.log(f"\n{'═' * 50}", "success")
            self.log(f"  ️ {domain} ha sido eliminado completamente.", "success")
            self.log(f"{'═' * 50}\n", "success")

            self.root.after(0, lambda: messagebox.showinfo(
                "Desinstalación completada",
                f"{domain} ha sido eliminado del servidor."
            ))

        except Exception as e:
            self.log(f"\nERROR: {str(e)}", "error")
            self.set_status("Error")
        finally:
            self.ssh_close()
            self.is_running = False
            self.root.after(0, lambda: self.install_btn.configure(state="normal"))
            self.root.after(0, lambda: self.uninstall_btn.configure(state="normal"))

    def _on_close(self):
        if self.is_running:
            if not messagebox.askyesno("Proceso en curso",
                                       "Hay una instalación en progreso. ¿Cerrar de todos modos?"):
                return
        self.ssh_close()
        self.root.destroy()


if __name__ == "__main__":
    root = tk.Tk()
    app = InstallerApp(root)
    root.mainloop()
