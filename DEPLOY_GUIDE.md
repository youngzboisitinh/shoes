# Hướng dẫn Deploy lên Render.com

## Bước 1: Tạo Render Service

1. Vào [render.com](https://render.com) và login
2. Click `New +` → `Web Service`
3. Chọn **Docker** image
4. Điền thông tin:
   - **Name**: `shoes-app`
   - **Registry**: `ghcr.io`
   - **Image URL**: `ghcr.io/youngzboisitinh/shoes:latest` (thay `youngzboisitinh` bằng username GitHub của bạn)
   - **Port**: `80`
5. Click **Deploy**
6. Sau khi tạo xong, copy **Service ID** từ URL hoặc Settings page

## Bước 2: Tạo GitHub Actions Secrets

1. Vào GitHub repo: https://github.com/youngzboisitinh/shoes
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret** và thêm 2 secrets:

### Secret 1: RENDER_API_KEY
- **Name**: `RENDER_API_KEY`
- **Value**: Lấy từ Render:
  - Vào [Render Account Settings](https://dashboard.render.com/account)
  - Scroll xuống **API Keys**
  - Click **Create API Key** (nếu chưa có)
  - Copy key đó

### Secret 2: RENDER_SERVICE_ID
- **Name**: `RENDER_SERVICE_ID`
- **Value**: Service ID từ bước 1 (có dạng `srv_xxxxxxx`)

## Bước 3: Kiểm tra Workflow chạy

1. Vào tab **Actions** trên GitHub repo
2. Thấy workflow `CI Build and Deploy to Render` chạy:
   - Build image Docker
   - Push lên GitHub Container Registry (GHCR)
   - Gọi Render API để deploy

## Bước 4: Kiểm tra Deploy trên Render

- Vào [Render Dashboard](https://dashboard.render.com)
- Click vào service `shoes-app`
- Xem logs để debug nếu có lỗi

## Troubleshooting

**Lỗi "Fatal: 'origin' does not appear to be a git repository"**
- Git remote chưa được setup → chạy: `git remote add origin https://github.com/<username>/<repo>.git`

**Lỗi MySQL Connection trong Docker**
- Container MySQL chưa sẵn sàng → thêm `healthcheck` và `depends_on` condition (đã có trong docker-compose.yml)
- Trên Render, cần thêm MySQL service riêng hoặc sử dụng managed database

**Workflow không chạy**
- Kiểm tra `.github/workflows/ci-deploy.yml` tồn tại không
- Kiểm tra branch name là `main` không (workflow bắt buộc `main` hoặc `master`)
- Kiểm tra có secret được set không

## Cấu trúc hiện tại

- `Dockerfile` — Docker image cho PHP + Apache
- `docker-compose.yml` — Local dev: web + MySQL
- `.github/workflows/ci-deploy.yml` — Tự động build + push + deploy
- `.dockerignore` — Bỏ qua file không cần khi build

