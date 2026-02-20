# Fitness Tracker - Proxmox LXC Deployment Guide

## Quick Start (5 minutes)

### 1. Create LXC Container in Proxmox

In Proxmox web UI:
1. Click **Create CT** (top right)
2. Configure:
   - **Hostname**: `fitness-tracker`
   - **Password**: Set a root password
   - **Template**: Debian 12 or Ubuntu 22.04 (download from CT Templates if needed)
   - **Disk**: 8 GB
   - **CPU**: 1 core
   - **Memory**: 512 MB
   - **Network**: DHCP or static IP on your LAN

3. Click **Create** → **Start**

### 2. Copy Files to Container

From your Windows machine, use WinSCP or SCP:

```powershell
# Using SCP (run in PowerShell)
scp -r "D:\Code\Fitness Tracker\Fitness-Tracker\fitness-tracker" root@<CONTAINER_IP>:/root/
scp "D:\Code\Fitness Tracker\Fitness-Tracker\setup-lxc.sh" root@<CONTAINER_IP>:/root/
```

Or via Proxmox shell:
1. Click on container → **Console**
2. Use `wget` or `curl` if files are hosted somewhere

### 3. Run Setup Script

SSH into the container or use Proxmox Console:

```bash
ssh root@<CONTAINER_IP>

# Make executable and run
chmod +x /root/setup-lxc.sh
cd /root
./setup-lxc.sh
```

### 4. Access Your App

Open browser: `http://<CONTAINER_IP>/`

---

## Optional: SSL with Let's Encrypt

If you want HTTPS (recommended for external access):

```bash
apt install certbot python3-certbot-apache
certbot --apache -d your-domain.com
```

---

## Optional: Reverse Proxy Setup

If you're using a reverse proxy (like Nginx Proxy Manager):

1. Point your domain to your Proxmox host
2. Create proxy entry pointing to the container IP
3. Enable SSL in the proxy manager

---

## Backup & Restore

### Backup (in Proxmox)
- Select container → **Backup** → **Backup now**
- Or set up scheduled backups in Datacenter → Backup

### Manual Database Backup
```bash
mysqldump -u fitness_user -p fitness_tracker > /root/fitness_backup_$(date +%Y%m%d).sql
```

---

## Troubleshooting

### Check Apache Status
```bash
systemctl status apache2
```

### Check Logs
```bash
tail -f /var/log/apache2/fitness-tracker-error.log
```

### Test Database Connection
```bash
mysql -u fitness_user -p -e "SELECT 1" fitness_tracker
```

### Restart Services
```bash
systemctl restart apache2
systemctl restart mariadb
```

---

## Container Resources

The app is very lightweight:
- **RAM**: ~100-150 MB in use
- **Disk**: ~500 MB total
- **CPU**: Minimal (PHP is fast for simple apps)

You can safely run this alongside many other containers.
