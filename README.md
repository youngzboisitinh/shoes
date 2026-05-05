# 🛍️ ShoeStore - Website Bán Giày Trực Tuyến
Dự án xây dựng một website thương mại điện tử bán giày với các chức năng cơ bản:
- 👟 Quản lý sản phẩm, danh mục, hình ảnh và biến thể (size, màu)
- 🛒 Giỏ hàng, thêm/xóa/sửa số lượng sản phẩm
- 💳 Quản lý đơn hàng, thanh toán (COD, BANK, BALANCE)
- 🎟️ Hỗ trợ mã giảm giá (Coupons)
- 👤 Quản lý tài khoản người dùng (đăng ký, đăng nhập, phân quyền admin/user)
- 📩 Liên hệ qua form hỗ trợ khách hàng
- 📊 Thống kê và quản lý trong giao diện quản trị
## Công nghệ sử dụng
- **Backend:** PHP (thuần)
- **Frontend:** HTML, CSS, Bootstrap 5, JavaScript, jQuery, AJAX
- **Database:** MySQL
- **Other:** Responsive Design, Utility Classes của Bootstrap

## Docker & CI/CD (Hướng dẫn tiếng Việt)

Tôi đã thêm cấu hình để đóng gói và deploy tự động:

- [Dockerfile](Dockerfile)
- [.dockerignore](.dockerignore)
- [.github/workflows/ci-deploy.yml](.github/workflows/ci-deploy.yml)

Các bước nhanh để đưa lên GitHub và deploy lên Render:

1) Khởi tạo git và đẩy mã lên GitHub (thay `<REPO_URL>` bằng URL repo của bạn):

```powershell
git init
git add .
git commit -m "Add Docker + CI"
git branch -M main
git remote add origin <REPO_URL>
git push -u origin main
```

2) Thêm secret vào GitHub repo: `Settings -> Secrets and variables -> Actions -> New repository secret`:
- `RENDER_API_KEY` — tạo API Key trong Render (Account -> API Keys)
- `RENDER_SERVICE_ID` — ID của Service trên Render (Service -> Settings -> Service Details)

3) Cách Render được kích hoạt:
- Workflow sẽ build image và push lên GitHub Container Registry: `ghcr.io/<owner>/<repo>:<sha>`.
- Nếu có `RENDER_API_KEY` và `RENDER_SERVICE_ID`, workflow sẽ gọi API của Render để tạo deploy mới dùng image vừa built.

4) Kiểm tra:
- Vào tab `Actions` trên GitHub để xem workflow chạy.
- Vào Render dashboard để xem trạng thái deploy.

Nếu bạn muốn, tôi có thể giúp thực hiện lệnh `git push` từ máy này (bạn cần cung cấp URL repo và xác thực nếu cần). Hoặc tôi có thể tự tạo một `README_DEPLOY.md` chi tiết hơn.

