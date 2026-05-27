import os
import sys
import zipfile
import subprocess

try:
    import paramiko
except ImportError:
    print("Installing paramiko locally...")
    subprocess.check_call([sys.executable, "-m", "pip", "install", "paramiko"])
    import paramiko

# Configuration
VPS_IP = "68.168.218.151"
VPS_USER = "root"
VPS_PASS = "4c7HVxX#"
APP_DIR = "/home/grupaqgl/web/bills.gridbase.com.do/public_html"
ZIP_PATH = "bills_deploy.zip"

EXCLUDE_DIRS = {
    'vendor', 'node_modules', '.git', '.github', '.gemini', 'scratch',
    'storage/framework/cache/data', 'storage/framework/sessions', 
    'storage/framework/views', 'storage/logs'
}

EXCLUDE_FILES = {
    '.env', ZIP_PATH, 'deploy_vps.py', 'vps_check.py', 'vps_db.py', 
    'vps_db_create.py', 'vps_env.py', 'vps_nginx.py'
}

def should_exclude(path, base_dir):
    rel_path = os.path.relpath(path, base_dir).replace('\\', '/')
    
    # Check direct file exclusion
    if rel_path in EXCLUDE_FILES or os.path.basename(path) in EXCLUDE_FILES:
        return True
        
    # Check directory exclusion
    for exc in EXCLUDE_DIRS:
        if rel_path == exc or rel_path.startswith(exc + '/'):
            return True
            
    return False

def zip_project(base_dir, zip_name):
    print(f"Compressing project from {base_dir}...")
    count = 0
    with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(base_dir):
            # Exclude directory search to optimize walks
            dirs[:] = [d for d in dirs if not should_exclude(os.path.join(root, d), base_dir)]
            
            for file in files:
                file_path = os.path.join(root, file)
                if not should_exclude(file_path, base_dir):
                    rel_path = os.path.relpath(file_path, base_dir)
                    zipf.write(file_path, rel_path)
                    count += 1
                    
    print(f"Compressed {count} files into {zip_name} successfully.")

def run_ssh_cmd(ssh, cmd):
    print(f"Executing: {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    
    # Wait for completion
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    
    if out:
        print(f"STDOUT:\n{out}")
    if err:
        print(f"STDERR:\n{err}")
    return out, err

def deploy():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    zip_project(base_dir, ZIP_PATH)
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        print(f"Connecting to VPS {VPS_IP}...")
        ssh.connect(VPS_IP, username=VPS_USER, password=VPS_PASS, timeout=30)
        
        # 1. Install Composer on VPS if not installed
        print("\n--- Verifying Composer ---")
        _, stdout, stderr = ssh.exec_command("composer --version")
        out = stdout.read().decode().strip()
        err = stderr.read().decode().strip()
        if "command not found" in out or "command not found" in err or not out:
            print("Installing Composer globally on VPS...")
            run_ssh_cmd(ssh, "curl -sS https://getcomposer.org/installer | php")
            run_ssh_cmd(ssh, "mv composer.phar /usr/local/bin/composer")
            run_ssh_cmd(ssh, "chmod +x /usr/local/bin/composer")
        else:
            print("Composer is already installed.")
            
        # 2. Upload ZIP file
        print("\n--- Uploading ZIP file ---")
        sftp = ssh.open_sftp()
        remote_zip = f"/home/grupaqgl/web/bills.gridbase.com.do/{ZIP_PATH}"
        sftp.put(ZIP_PATH, remote_zip)
        sftp.close()
        print("Upload completed successfully.")
        
        # 3. Clean and Unzip
        print("\n--- Extracting files ---")
        # Remove default files
        run_ssh_cmd(ssh, f"rm -rf {APP_DIR}/*")
        # Extract
        run_ssh_cmd(ssh, f"unzip -o {remote_zip} -d {APP_DIR}")
        # Clean remote zip
        run_ssh_cmd(ssh, f"rm -f {remote_zip}")
        
        # 4. Configure .env
        print("\n--- Setting up environment config (.env) ---")
        sftp = ssh.open_sftp()
        env_exists = False
        try:
            sftp.stat(f"{APP_DIR}/.env")
            env_exists = True
            print(".env file already exists on server. Preserving it.")
        except IOError:
            pass
            
        if not env_exists:
            env_content = """APP_NAME="Gridbase Bills"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://bills.gridbase.com.do

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grupaqgl_bills
DB_USERNAME=grupaqgl_bills
DB_PASSWORD=SamDP_9903_db

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@bills.gridbase.com.do"
MAIL_FROM_NAME="Gridbase Bills"
"""
            with sftp.file(f"{APP_DIR}/.env", 'w') as f:
                f.write(env_content)
            print(".env file written.")
        sftp.close()
        
        # 5. Fix permissions before composer and key gen
        print("\n--- Correcting Ownership & Permissions ---")
        run_ssh_cmd(ssh, f"chown -R grupaqgl:grupaqgl {APP_DIR}")
        run_ssh_cmd(ssh, f"mkdir -p {APP_DIR}/storage/framework/cache/data {APP_DIR}/storage/framework/sessions {APP_DIR}/storage/framework/views {APP_DIR}/storage/logs {APP_DIR}/bootstrap/cache")
        run_ssh_cmd(ssh, f"chown -R grupaqgl:www-data {APP_DIR}/storage {APP_DIR}/bootstrap/cache")
        run_ssh_cmd(ssh, f"chmod -R 775 {APP_DIR}/storage {APP_DIR}/bootstrap/cache")
        
        # 6. Generate Key
        if not env_exists:
            print("\n--- Generating Application Key ---")
            run_ssh_cmd(ssh, f"sudo -u grupaqgl php {APP_DIR}/artisan key:generate")
        else:
            print("\n--- Preserving Existing Application Key ---")
        
        # 7. Install Composer Dependencies
        print("\n--- Installing Composer Dependencies ---")
        run_ssh_cmd(ssh, f"cd {APP_DIR} && sudo -u grupaqgl composer install --no-dev --optimize-autoloader --no-interaction")
        
        # 8. Run Database Migrations
        print("\n--- Running Migrations ---")
        run_ssh_cmd(ssh, f"sudo -u grupaqgl php {APP_DIR}/artisan migrate --force")
        
        # 9. Clear cache
        print("\n--- Optimizing Laravel Cache ---")
        run_ssh_cmd(ssh, f"sudo -u grupaqgl php {APP_DIR}/artisan config:cache")
        run_ssh_cmd(ssh, f"sudo -u grupaqgl php {APP_DIR}/artisan route:cache")
        run_ssh_cmd(ssh, f"sudo -u grupaqgl php {APP_DIR}/artisan view:cache")
        
        print("\n==========================================")
        print("DEPLOYMENT TO VPS COMPLETED SUCCESSFULLY!")
        print("==========================================")
        
    except Exception as e:
        print("\nDeployment failed with error:", e)
    finally:
        ssh.close()
        # Clean up local zip
        if os.path.exists(ZIP_PATH):
            os.remove(ZIP_PATH)

if __name__ == '__main__':
    deploy()
