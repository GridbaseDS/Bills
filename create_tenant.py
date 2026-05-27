import sys
import subprocess

try:
    import paramiko
except ImportError:
    print("Instalando paramiko localmente...")
    subprocess.check_call([sys.executable, "-m", "pip", "install", "paramiko"])
    import paramiko

# Configuration
VPS_IP = "68.168.218.151"
VPS_USER = "root"
VPS_PASS = "4c7HVxX#"
APP_DIR = "/home/grupaqgl/web/bills.gridbase.com.do/public_html"

def run_ssh_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    return out, err

def main():
    if len(sys.argv) < 2:
        print("Uso: python create_tenant.py <subdominio>")
        print("Ejemplo: python create_tenant.py empresa1")
        subdomain = input("Escribe el nombre del subdominio de la empresa: ").strip().lower()
    else:
        subdomain = sys.argv[1].strip().lower()
        
    if not subdomain:
        print("El subdominio no puede estar vacío.")
        return
        
    db_name = f"grupaqgl_{subdomain}"
    print(f"\nPreparando migraciones para la empresa con subdominio: '{subdomain}'")
    print(f"Base de datos objetivo: '{db_name}'")
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        print(f"Conectando al VPS {VPS_IP}...")
        ssh.connect(VPS_IP, username=VPS_USER, password=VPS_PASS, timeout=15)
        
        # 1. Clear config cache so Laravel reads the dynamic DB_DATABASE env var
        print("Limpiando caché de configuración en el VPS...")
        run_ssh_cmd(ssh, f"sudo -u grupaqgl php {APP_DIR}/artisan config:clear")
        
        # 2. Run migrations using the dynamic DB name
        print(f"\nEjecutando php artisan migrate --force en {db_name}...")
        cmd = f"sudo -u grupaqgl DB_DATABASE={db_name} php {APP_DIR}/artisan migrate --force"
        stdin, stdout, stderr = ssh.exec_command(cmd)
        out = stdout.read().decode().strip()
        err = stderr.read().decode().strip()
        
        # 2.5 Run database seeding for the new tenant
        print(f"Ejecutando php artisan db:seed --force en {db_name}...")
        cmd_seed = f"sudo -u grupaqgl DB_DATABASE={db_name} php {APP_DIR}/artisan db:seed --force"
        stdin_s, stdout_s, stderr_s = ssh.exec_command(cmd_seed)
        out_s = stdout_s.read().decode().strip()
        err_s = stderr_s.read().decode().strip()
        if out_s:
            out += "\n\nSEEDER:\n" + out_s
        if err_s:
            err += "\n\nSEEDER ERR:\n" + err_s
        
        # 3. Re-cache config for maximum performance
        print("\nRe-optimizando caché de configuración...")
        run_ssh_cmd(ssh, f"sudo -u grupaqgl php {APP_DIR}/artisan config:cache")
        
        if out:
            print("\n--- SALIDA ---")
            print(out)
        if err:
            print("\n--- ERRORES / ADVERTENCIAS ---")
            print(err)
            
        print("\n=======================================================")
        print("¡PROCESO COMPLETADO EXITOSAMENTE!")
        print("=======================================================")
        print(f"1. Asegúrate de haber creado la base de datos '{db_name}' en HestiaCP.")
        print(f"2. Ahora ya puedes ingresar a: https://{subdomain}.bills.gridbase.com.do")
        print("=======================================================")
        
    except Exception as e:
        print("Error durante la conexión:", e)
    finally:
        ssh.close()

if __name__ == '__main__':
    main()
