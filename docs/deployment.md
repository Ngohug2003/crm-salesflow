# Deployment

Development uses `docker compose up -d --build`. The PHP image is shared by the FPM app, Horizon, scheduler and Reverb. Nginx is the only public application entry point; PostgreSQL, Redis and MinIO remain on the private Compose network. Production must inject secrets, enable HTTPS/secure cookies, use S3-compatible object storage, rotate logs, back up PostgreSQL, and run `php artisan optimize` during release.

