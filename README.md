# 📸 MikroTik Payment Reminder Image Host

A **minimalist PHP + SQLite web app** that displays a **rotating image carousel** for **MikroTik Hotspot or Web Proxy payment reminders**.

Designed for **ISPs** and **network administrators**, it serves as a lightweight, self-contained web interface for displaying announcements or payment notices when user access is restricted.

Unlike most setups that rely on external image URLs, this app **hosts all images locally**, ensuring reliability even without internet access — perfect for **offline or isolated network environments**.

Runs **anywhere** — from a full Docker server to **MikroTik RouterOS v7 containers**.

---

## ⚙️ Overview

| Feature                        | Description                                                                             |
| ------------------------------ | --------------------------------------------------------------------------------------- |
| 💻 **Device Detection**        | Automatically detects mobile or desktop clients to serve the right image.               |
| 🗂 **Local-Only Setup**        | No external dependencies or CDNs — ideal for offline or isolated networks.              |
| 🧠 **SQLite Storage**          | Lightweight configuration and settings database.                                        |
| 🐳 **Docker & MikroTik Ready** | Designed for embedded and low-resource container environments.                          |
| 🎞 **Dynamic Carousel**        | Smooth, auto-rotating background image carousel for announcements or payment reminders. |

---

## 🏗 Project Structure

```
mikrotik-payment-reminder/
│
├── uploads/              # Image files displayed in the carousel
├── database.sqlite       # SQLite database file (auto-created on first run)
├── index.php             # Main application
├── upload.php            # Basic app configuration page
└── Dockerfile            # Lightweight PHP web server container
```

---

## 🐳 Run via Docker

### 1. Pull the container image

```bash
docker pull kintoyyy/mikrotik-payment-reminder
```

### 2. Start the container

```bash
docker run -d --name payment-reminder -p 8080:80 -v $(pwd)/uploads:/var/www/html/uploads -v $(pwd)/database.sqlite:/var/www/html/database.sqlite kintoyyy/mikrotik-payment-reminder
```

Once running, open:

```
http://localhost:8080
```

### 3. Manage Uploads and Settings

You can manage the carousel images and configuration directly through the built-in **admin interface**.

#### 🔐 Access the Admin Panel

Open your browser and go to:

```
http://localhost:8080/upload.php
```

**Default credentials:**

```
Username: admin
Password: admin
```

#### 🖼 Upload Images

Once logged in:

1. Navigate to the **Upload Images** section.
2. Select your payment reminder images (e.g., `.jpg`, `.png`, `.gif`).
3. Click **Upload** — the images will be stored locally in the `/uploads/` directory.

All uploaded files are hosted **locally**, so the app does **not** depend on any third-party hosting or internet connectivity.

#### ⚙ Modify Settings

Inside the same panel, you can also:

* Adjust the **carousel rotation duration**
* Toggle **mobile/desktop image sets**
* Manage or delete existing uploads

Changes are saved automatically in the local **SQLite database** (`database.sqlite`).

---


## 🚀 Deploying “MikroTik Payment Reminder” on RouterOS v7 (with Container Support)

> 🧩 **Tested on:** RouterOS v7.14+
> ⚙️ **Requirements:**
>
> * Router with container support (e.g., RB5009, CHR, x86)
> * Sufficient storage (e.g., `disk1`)
> * Internet access for pulling Docker images

---

### 1️⃣ Enable Container Package

```rsc
/system/device-mode/update container=yes
# Reboot after enabling
/system/reboot
```

---

### 2️⃣ Create Required Mounts

```rsc
/container/mounts
add dst="/uploads" name=MOUNT_PAYMENT_UPLOADS src="/var/www/html/uploads"
add dst="/database.sqlite" name=MOUNT_SQLITE src="/var/www/html/database.sqlite"
```

---

### 3️⃣ Create Bridge and VETH Interface

```rsc
/interface/bridge
add name=containers

/interface/veth
add name=veth-payment-reminder address=172.17.0.2/30 gateway=172.17.0.1

/interface/bridge/port
add bridge=containers interface=veth-payment-reminder
```

---

### 4️⃣ Configure the Container

```rsc
/container/config
set registry-url="https://registry-1.docker.io" tmpdir="disk1/tmp"

/container
add name="mikrotik-payment-reminder" \
    interface=veth-payment-reminder \
    logging=yes \
    mounts=MOUNT_PAYMENT_UPLOADS,MOUNT_SQLITE \
    root-dir="disk1/images/payment-reminder" \
    remote-image="kintoyyy/mikrotik-payment-reminder" \
    workdir="/"

# Start the container
/container/start mikrotik-payment-reminder
```

---

### 5️⃣ Configure Network and NAT Rules

```rsc
/ip/address
add address=172.17.0.1/30 interface=containers network=172.17.0.0

/ip/firewall/nat
add chain=srcnat action=masquerade src-address=172.17.0.0/30
add chain=dstnat action=dst-nat protocol=tcp dst-port=80 \
    to-addresses=172.17.0.2 to-ports=80
```

---

### 6️⃣ Create PPP Profile for Expired Users

```rsc
/ip/pool
add name=EXPIRED ranges=10.254.0.10-10.254.0.250

/ppp/profile
add name=EXPIRED local-address=10.254.0.1 remote-address=EXPIRED \
    dns-server=172.17.0.1 address-list=EXPIRED rate-limit=128k/128k
```

---

### 7️⃣ Restrict Expired Users & Redirect to Reminder Page

```rsc
/ip/firewall/address-list
add list=WHITELIST address=172.17.0.2

/ip/firewall/nat
add chain=dstnat action=redirect to-ports=8082 protocol=tcp \
    src-address-list=EXPIRED dst-port=80,443 dst-address-list=!WHITELIST

/ip/firewall/filter
add chain=forward action=drop protocol=tcp src-address-list=EXPIRED \
    dst-port=80,443 dst-address-list=!WHITELIST
```

---

### 8️⃣ Configure MikroTik Proxy for Redirection

```rsc
/ip/proxy
set enabled=yes port=8082 parent-proxy=0.0.0.0

/ip/proxy/access
remove [find]
add action=allow src-address=10.254.0.0/24
add action=redirect redirect-to=172.17.0.2:80 \
    src-address=10.254.0.0/24 dst-port=80,443
add action=deny src-address=10.254.0.0/24
```

---

### 9️⃣ Access the Admin Panel

Once the container is running and NAT configured, open:

```
http://172.17.0.2/upload.php
```

**Default Credentials:**

```
Username: admin
Password: admin
```

---

### ✅ Optional: Check Container Status

```rsc
/container/print
/container/logs mikrotik-payment-reminder follow=yes
```

---

## 🧠 Usage Notes

* The carousel automatically cycles through uploaded images.
* If you want faster load times, use compressed `.jpg` or `.png` images.
* The SQLite file is persistent — it stores configuration settings like carousel duration.

---

## 📜 License

**MIT License**
Free for personal and commercial use. Attribution appreciated.

---
