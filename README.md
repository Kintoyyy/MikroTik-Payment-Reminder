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

## 🔧 Example: Running on MikroTik RouterOS v7 (Container Support)

> Tested on RouterOS 7.14+ with Docker-compatible container support.

### 1. Create required mounts

```rsc
/container mounts
add dst="/uploads" name=MOUNT_PAYMENT_UPLOADS src="/var/www/html/uploads"
add dst="/database.sqlite" name=MOUNT_SQLITE src="/var/www/html/database.sqlite"
```

### 2. Create bridge and veth interface

```rsc
/interface bridge
add name=containers

/interface veth
add address=172.17.0.2/30 gateway=172.17.0.1 name=veth-payment-reminder

/interface bridge port
add bridge=containers interface=veth-payment-reminder
```

### 3. Configure container

```rsc
/container config
set registry-url="https://registry-1.docker.io" tmpdir="disk1/tmp"

/container
add interface=veth-payment-reminder logging=yes mounts=MOUNT_PAYMENT_UPLOADS,MOUNT_SQLITE name="kintoyyy/mikrotik-payment-reminder:latest" root-dir="disk1/images/payment-reminder" workdir="/"
```

### 4. Assign IP and NAT rules

```rsc
/ip address
add address=172.17.0.1/30 interface=containers network=172.17.0.0

/ip firewall nat
add action=masquerade chain=srcnat src-address=172.17.0.0/30
add action=dst-nat chain=dstnat dst-port=80 protocol=tcp to-addresses=172.17.0.2 to-ports=80
```

### 5. Create PPP profile for expired users

```rsc
/ip pool
add name=EXPIRED ranges=10.254.0.10-10.254.0.250

/ppp profile
add address-list=EXPIRED dns-server=172.17.0.1 local-address=10.254.0.1 name=EXPIRED rate-limit=128k/128k remote-address=EXPIRED
```

### 6. Restrict expired users and redirect to reminder page

```rsc
/ip firewall address-list
add address=172.17.0.2 list=WHITELIST

/ip firewall nat
add action=redirect chain=dstnat dst-address-list=!WHITELIST dst-port=80,443 protocol=tcp src-address-list=EXPIRED to-ports=8082

/ip firewall filter
add action=drop chain=forward dst-address-list=!WHITELIST dst-port=80,443 protocol=tcp src-address-list=EXPIRED
```

### 7. Configure MikroTik proxy for redirection

```rsc
/ip proxy
set enabled=yes port=8082

/ip proxy access
add dst-port=8082 src-address=10.254.0.0/24
add action=redirect action-data=172.17.0.2:80 dst-address=!172.17.0.1 dst-host=!172.17.0.2 dst-port=80,443 src-address=10.254.0.0/24
add action=deny src-address=10.254.0.0/24
```

#### 🔐 Access the Admin Panel

Open your browser and go to:

```
http://172.17.0.2:80/upload.php
```

**Default credentials:**

```
Username: admin
Password: admin
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
