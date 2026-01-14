<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Deployment Guide for AWS EC2

This guide details the steps to deploy the Laravel Video Downloader application to an AWS EC2 instance with Docker and forced HTTPS.

### Prerequisites

1.  **AWS EC2 Instance**:
    - Recommended OS: Ubuntu 22.04 LTS or 24.04 LTS.
    - Instance Type: t3.small or larger (for memory/CPU relative to video processing).
    - Security Group Inbound Rules:
        - SSH (22) - Your IP
        - HTTP (80) - Anywhere (0.0.0.0/0)
        - HTTPS (443) - Anywhere (0.0.0.0/0)

2.  **Domain Name**:
    - Point the A record for `ytd.jyu1999.com` to your EC2 instance's Public IP address.

### Step 1: Server Setup

SSH into your EC2 instance:
```bash
ssh -i /path/to/your/key.pem ubuntu@your-ec2-ip
```

Install Docker and Docker Compose:
```bash
# Add Docker's official GPG key:
sudo apt-get update
sudo apt-get install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update

# Install Docker packages:
sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Allow running docker without sudo (re-login required after this):
sudo usermod -aG docker $USER
```
*Logout and login again for group changes to take effect.*

### Step 2: Application Setup

Clone the repository:
```bash
git clone https://github.com/jyu1999/laravel-yt-dlp.git
cd laravel-yt-dlp
```

Configure Environment Variables:
```bash
cp .env.example .env
nano .env
```
> [!IMPORTANT]
> Update the following `.env` variables for production:
> - `APP_ENV=production`
> - `APP_DEBUG=false`
> - `APP_URL=https://ytd.jyu1999.com`
> - `DB_PASSWORD=your_secure_password`
> - `REDIS_PASSWORD=your_secure_redis_password` (if applicable)
> - `REVERB_APP_KEY`, `REVERB_APP_SECRET` (generate strong random strings)

### Step 3: SSL Initialization

Run the helper script to generate Let's Encrypt certificates. This script creates a dummy certificate, starts Nginx, requests the real certificate, and then reloads Nginx.

```bash
chmod +x init-letsencrypt.sh
./init-letsencrypt.sh
```
Follow the prompts if asked (you shouldn't need to do much as it is automated).

### Step 4: First Deployment

Run the deploy script to build containers, run migrations, and optimize:
```bash
chmod +x deploy.sh
./deploy.sh
```

**Important**: After the first deployment, you must generate the application key if you haven't already:
```bash
docker compose -f docker-compose.prod.yml exec app php artisan key:generate
```

### Step 5: Verification

1.  Open [https://ytd.jyu1999.com](https://ytd.jyu1999.com) in your browser.
2.  Ensure you are redirected to HTTPS.
3.  Check the padlock icon to verify the SSL certificate.
4.  Login and test downloading a video to ensure `yt-dlp` and Reverb (WebSockets) are working.

### Subsequent Updates

To deploy new changes in the future, simply SSH into the server and run:
```bash
cd laravel-yt-dlp
./deploy.sh
```
