---
stepsCompleted: ['step-01-validate-prerequisites', 'step-02-design-epics', 'step-03-create-stories']
inputDocuments:
  - '_bmad-output/planning-artifacts/prd.md'
  - '_bmad-output/planning-artifacts/architecture.md'
  - '_bmad-output/planning-artifacts/implementation-readiness-report-2026-01-13.md'
---

# laravel-yt-dlp - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for laravel-yt-dlp, decomposing the requirements from the PRD, Architecture, and Implementation Readiness Report into implementable stories.

## Requirements Inventory

### Functional Requirements

**用户与角色管理**
- FR1: 访客可以匿名使用下载功能直到达到匿名配额上限
- FR2: 访客可以注册账号
- FR3: 注册用户可以登录并使用注册用户配额
- FR4: 系统能区分匿名用户、注册用户、管理员三种角色
- FR5: 管理员账号可获得无广告、无限配额的特权

**链接与视频信息**
- FR6: 用户可以提交 YouTube 链接进行解析
- FR7: 系统可以验证链接有效性并返回错误提示
- FR8: 系统可以展示视频标题、时长、文件大小
- FR9: 用户可以选择视频格式（例如 MP4、MOV）
- FR10: 系统可以自动选择最佳画质与音质组合

**字幕能力**
- FR11: 用户可以选择是否下载字幕
- FR12: 用户可以选择字幕语言
- FR13: 系统可以在下载完成时提供字幕文件

**下载执行与交付**
- FR14: 用户可以启动下载
- FR15: 系统可以为用户提供下载进度更新
- FR16: 下载完成后系统能交付文件给用户
- FR17: 系统能在下载失败时提供原因提示

**配额与速率限制**
- FR18: 系统能对匿名用户施加下载配额限制
- FR19: 系统能对注册用户施加每小时与每日配额限制
- FR20: 系统能显示当前用户剩余配额
- FR21: 系统能在达到配额时阻止下载并提示

**广告与变现**
- FR22: 系统可对非管理员用户展示广告
- FR23: 系统可对管理员隐藏广告

**管理后台与运营**
- FR24: 管理员可以查看下载、用户、活跃度统计
- FR25: 管理员可以查看系统运行状态
- FR26: 管理员可以查看收入或广告展示统计
- FR27: 管理员可以查看错误或失败记录

**用户与滥用治理**
- FR28: 管理员可以查看用户详情（含下载记录、IP、UA）
- FR29: 管理员可以封锁用户账号
- FR30: 管理员可以封锁 IP 并设置期限
- FR31: 系统可以检测非浏览器请求并阻止
- FR32: 系统可以识别异常下载行为并告警

**合规与条款**
- FR33: 系统可以展示免责声明与使用条款
- FR34: 系统可以要求用户同意使用条款后使用服务

**维护与版本管理**
- FR35: 管理员可以查看当前 yt-dlp 版本信息
- FR36: 管理员可以发布系统公告

**数据保留与清理**
- FR37: 系统可以按配置周期清理下载记录
- FR38: 系统可以按配置周期清理错误日志
- FR39: 系统可以按配置周期清理 IP 记录

### NonFunctional Requirements

**Performance**
- NFR1: 视频信息展示在提交链接后 ≤ 5 秒完成
- NFR2: 下载开始响应在用户点击后 ≤ 1 秒确认
- NFR3: 下载进度更新频率 ≥ 每 500ms
- NFR4: 平均下载完成时间（从提交到完成）≤ 3 分钟（常见视频）

**Reliability**
- NFR5: 系统整体下载成功率 ≥ 95%
- NFR6: 系统可用性 ≥ 99%（计划维护除外）

**Security & Abuse Prevention**
- NFR7: 账号密码使用安全哈希（bcrypt）存储
- NFR8: 非浏览器请求默认拦截并可配置
- NFR9: 触发异常行为时可自动告警
- NFR10: 所有用户交互必须通过 HTTPS

**Scalability**
- NFR11: 系统同时处理下载任务 ≤ 10
- NFR12: 单用户并发下载 ≤ 1

**Compliance & Data Retention**
- NFR13: 下载记录按策略自动清理（匿名 24h、注册 90d）
- NFR14: IP 记录保留 30 天（封锁期间例外）
- NFR15: 错误日志保留 30 天
- NFR16: 注册必须确认使用条款与免责声明

### Additional Requirements

**From Architecture Document:**

**Starter Template & Project Initialization:**
- Use Laravel Livewire Starter Kit (Blade-first) as the project foundation
- Initialization command: `laravel new laravel-yt-dlp` with Livewire starter kit selection
- This provides built-in auth scaffolding, UI components (Flux), and Tailwind CSS
- **CRITICAL: Project initialization using this command should be the first implementation story (Epic 1, Story 1)**

**Technology Stack Requirements:**
- PHP 8.4 + Laravel 12
- PostgreSQL 17.7 (Dockerized)
- Redis 8.4.0 (Dockerized) for cache and queue
- Nginx 1.28.1 (stable) + php:8.4-fpm
- Laravel Reverb for WebSocket real-time features
- Laravel Fortify for session-based authentication with email verification
- Vite for asset bundling
- Tailwind CSS + Flux UI component library

**Infrastructure & Deployment:**
- Docker Compose for both development and production
- AWS EC2 single instance hosting model
- All services (web, app, queue, Reverb, PostgreSQL, Redis) containerized
- Nginx reverse proxy for WebSocket connections

**Real-time Communication:**
- Laravel Reverb + Laravel Echo for download progress updates
- WebSocket events: `DownloadProgress`, `DownloadCompleted`, `DownloadFailed`
- Fallback to polling (every 2 seconds) if WebSocket connection fails
- Admin dashboard real-time monitoring via WebSocket

**API & Communication Patterns:**
- REST-style JSON APIs for download lifecycle
- API endpoints: `POST /downloads` (create job), `GET /downloads/{id}` (status/progress)
- Error response format: `{ error: { code, message, details? } }`
- Success response format: `{ data, meta? }`
- Signed URLs for secure file delivery

**Security Implementation:**
- HTTPS mandatory for all user interactions
- CSRF protection via Laravel middleware
- bcrypt for password hashing
- User-Agent detection to block non-browser requests
- CAPTCHA at registration (Google reCAPTCHA v2 or hCaptcha)
- Rate limiting via throttle middleware
- Email verification required before service access

**Queue & Concurrency:**
- Laravel Queue with Redis backend
- Queue workers process download tasks
- System-wide limit: maximum 10 concurrent downloads
- Per-user limit: maximum 1 concurrent download
- Download jobs broadcast progress via Redis + events

**Data Architecture:**
- PostgreSQL 17.7 with Eloquent ORM
- Laravel migrations for schema management
- Redis cache for video metadata (TTL ~15 minutes)
- Database naming: snake_case for tables/columns
- Foreign keys: `<table>_id` format

**SEO Requirements:**
- Server-side rendering (SSR) via Laravel Blade
- Meta tags optimization (title, description, keywords)
- Open Graph tags for social sharing
- Twitter Card tags
- XML sitemap generation
- Structured data (Schema.org JSON-LD)
- Google Search Console and Analytics integration

**Responsive Design:**
- Mobile-first design approach
- Breakpoints: mobile (<640px), tablet (640-1024px), desktop (>1024px)
- Minimum click target: 44x44px for mobile
- Full-width layouts on mobile, centered layouts on desktop

**Browser Support:**
- Desktop: Latest 2 major versions of Chrome, Firefox, Safari, Edge
- Mobile: Safari iOS 14+, Chrome Android 8+
- No Internet Explorer support
- WebSocket with polling fallback for compatibility

**Project Structure:**
- Domain-driven structure with `app/Domain/Downloads`, `app/Domain/Users`, `app/Domain/Admin`
- Services in `app/Services`
- Jobs in `app/Jobs`
- Events/Listeners in `app/Events`, `app/Listeners`
- Livewire components in `app/Livewire` and `resources/views/livewire`
- Actions in `app/Actions`

**Naming Conventions:**
- Database: snake_case (tables: `download_tasks`, columns: `user_id`)
- API: snake_case for JSON fields, plural resource names (`/downloads`)
- PHP classes: StudlyCase (`DownloadJob`)
- Livewire views: kebab-case

**Performance Targets:**
- First Contentful Paint (FCP) < 1.5s
- Largest Contentful Paint (LCP) < 2.5s
- First Input Delay (FID) < 100ms
- Cumulative Layout Shift (CLS) < 0.1
- Time to Interactive (TTI) < 3.5s

**Monitoring & Logging:**
- Instance logs + optional CloudWatch agent
- Laravel Telescope for development performance analysis
- Error logging with 30-day retention
- System stats broadcast to admin dashboard every 5 seconds

**CI/CD Pipeline:**
- GitHub Actions for CI/CD
- Build Docker images on push
- Deploy via SSH to EC2 + docker-compose commands

### FR Coverage Map

**Epic 1 - Functional MVP - Core Video Download System:**
- FR5: 管理员账号可获得无广告、无限配额的特权
- FR6: 用户可以提交 YouTube 链接进行解析
- FR7: 系统可以验证链接有效性并返回错误提示
- FR8: 系统可以展示视频标题、时长、文件大小
- FR9: 用户可以选择视频格式（例如 MP4、MOV）
- FR10: 系统可以自动选择最佳画质与音质组合
- FR11: 用户可以选择是否下载字幕
- FR12: 用户可以选择字幕语言
- FR13: 系统可以在下载完成时提供字幕文件
- FR14: 用户可以启动下载
- FR15: 系统可以为用户提供下载进度更新
- FR16: 下载完成后系统能交付文件给用户
- FR17: 系统能在下载失败时提供原因提示

**Epic 2 - User System & Authentication:**
- FR1: 访客可以匿名使用下载功能直到达到匿名配额上限
- FR2: 访客可以注册账号
- FR3: 注册用户可以登录并使用注册用户配额
- FR4: 系统能区分匿名用户、注册用户、管理员三种角色

**Epic 3 - Quota Management & Rate Limiting:**
- FR18: 系统能对匿名用户施加下载配额限制
- FR19: 系统能对注册用户施加每小时与每日配额限制
- FR20: 系统能显示当前用户剩余配额
- FR21: 系统能在达到配额时阻止下载并提示

**Epic 4 - Abuse Prevention & Security Hardening:**
- FR31: 系统可以检测非浏览器请求并阻止
- FR32: 系统可以识别异常下载行为并告警

**Epic 5 - Admin Operations & Monitoring Dashboard:**
- FR24: 管理员可以查看下载、用户、活跃度统计
- FR25: 管理员可以查看系统运行状态
- FR26: 管理员可以查看收入或广告展示统计
- FR27: 管理员可以查看错误或失败记录
- FR28: 管理员可以查看用户详情（含下载记录、IP、UA）
- FR29: 管理员可以封锁用户账号
- FR30: 管理员可以封锁 IP 并设置期限
- FR35: 管理员可以查看当前 yt-dlp 版本信息

**Epic 6 - Monetization & Legal Framework:**
- FR22: 系统可对非管理员用户展示广告
- FR23: 系统可对管理员隐藏广告
- FR33: 系统可以展示免责声明与使用条款
- FR34: 系统可以要求用户同意使用条款后使用服务

**Epic 7 - Data Lifecycle & System Maintenance:**
- FR36: 管理员可以发布系统公告
- FR37: 系统可以按配置周期清理下载记录
- FR38: 系统可以按配置周期清理错误日志
- FR39: 系统可以按配置周期清理 IP 记录

## Epic List

### Epic 1: Functional MVP - Core Video Download System
Management and users can download YouTube videos with format and subtitle selection, and view real-time download progress in a local environment (Phase 1 complete).

**FRs covered:** FR5, FR6, FR7, FR8, FR9, FR10, FR11, FR12, FR13, FR14, FR15, FR16, FR17

---

### Epic 2: User System & Authentication
Visitors can register accounts, registered users can log in and use the service, and the system can distinguish between anonymous users, registered users, and administrators.

**FRs covered:** FR1, FR2, FR3, FR4

---

### Epic 3: Quota Management & Rate Limiting
The system can control usage for different users, with different download quotas for anonymous and registered users, and users can view their remaining quota.

**FRs covered:** FR18, FR19, FR20, FR21

---

### Epic 4: Abuse Prevention & Security Hardening
The system can prevent abuse and automated attacks, protecting service stability and resources.

**FRs covered:** FR31, FR32

---

### Epic 5: Admin Operations & Monitoring Dashboard
Administrators can monitor system operational status, view statistics, manage users, and handle abuse.

**FRs covered:** FR24, FR25, FR26, FR27, FR28, FR29, FR30, FR35

---

### Epic 6: Monetization & Legal Framework
The system can monetize through advertising and meet legal compliance requirements, with users understanding terms and responsibilities.

**FRs covered:** FR22, FR23, FR33, FR34

---

### Epic 7: Data Lifecycle & System Maintenance
The system can automatically maintain data (retention and cleanup), and administrators can publish announcements and manage tool versions.

**FRs covered:** FR36, FR37, FR38, FR39

---

## Epic 1: Functional MVP - Core Video Download System

**目标：** 管理员和用户可以下载 YouTube 视频，选择格式和字幕，并在本地环境中查看实时下载进度（Phase 1 完成）

**覆盖的 FRs：** FR5, FR6, FR7, FR8, FR9, FR10, FR11, FR12, FR13, FR14, FR15, FR16, FR17

### Story 1.1: 使用 Laravel Livewire Starter Kit 初始化项目

As a 开发者,
I want 使用 Laravel Livewire Starter Kit 初始化项目,
So that 我有一个具备认证脚手架、UI 组件和 Tailwind CSS 的基础项目结构。

**Acceptance Criteria:**

**Given** 开发环境已安装 Composer 和 Laravel installer
**When** 执行 `laravel new laravel-yt-dlp` 并选择 Livewire starter kit
**Then** 项目成功创建，包含 Laravel 12、Livewire 3、Flux UI、Tailwind CSS
**And** 执行 `npm install && npm run build` 成功编译前端资源
**And** 执行 `php artisan serve` 可以访问默认欢迎页面
**And** 认证脚手架（登录、注册页面）已可用

### Story 1.2: 配置 Docker Compose 开发环境

As a 开发者,
I want 配置完整的 Docker Compose 开发环境,
So that 本地开发环境与生产环境一致，包含所有必需服务。

**Acceptance Criteria:**

**Given** 项目已初始化
**When** 创建 `docker-compose.yml` 配置文件
**Then** 包含以下服务：PostgreSQL 17.7, Redis 8.4.0, Nginx 1.28.1, php:8.4-fpm
**And** Laravel Reverb 容器配置完成
**And** 队列 worker 容器配置完成
**And** 执行 `docker-compose up -d` 所有服务正常启动
**And** Laravel 应用可以连接到 PostgreSQL 和 Redis

### Story 1.3: 集成 yt-dlp 并验证基本功能

As a 开发者,
I want 在 Docker 环境中集成 yt-dlp 工具,
So that 系统可以调用 yt-dlp 下载 YouTube 视频。

**Acceptance Criteria:**

**Given** Docker 环境已配置
**When** 在 PHP-FPM 容器中安装 yt-dlp
**Then** 可以通过命令行执行 `yt-dlp --version` 查看版本信息
**And** 可以通过 PHP `exec()` 或 `Process` 调用 yt-dlp
**And** 测试下载一个 YouTube 视频（短视频）成功
**And** 可以获取视频信息（标题、时长、文件大小）

### Story 1.4: 创建视频信息获取服务

As a 用户,
I want 提交 YouTube 链接并获取视频信息,
So that 我可以在下载前了解视频详情（标题、时长、文件大小、可用格式）。

**Acceptance Criteria:**

**Given** 用户在下载页面
**When** 用户提交有效的 YouTube 链接
**Then** 系统调用 yt-dlp 获取视频元数据（≤ 5秒完成）
**And** 显示视频标题、时长、预估文件大小
**And** 显示可用格式列表（MP4、MOV）
**And** 显示可用字幕语言列表
**And** 视频信息缓存到 Redis（TTL 15分钟）

**Given** 用户提交无效的 YouTube 链接
**When** 系统验证链接
**Then** 返回友好的错误提示："链接无效或视频不存在"

### Story 1.5: 创建下载页面 UI

As a 用户,
I want 一个简洁的下载页面,
So that 我可以输入链接、选择格式和字幕、启动下载。

**Acceptance Criteria:**

**Given** 用户访问主页
**When** 页面加载
**Then** 显示一个大的 YouTube 链接输入框
**And** 提供 "获取视频信息" 按钮

**Given** 视频信息已获取
**When** 信息显示在页面上
**Then** 用户可以选择视频格式（MP4 / MOV 下拉选择）
**And** 用户可以勾选 "下载字幕" 复选框
**And** 如果勾选字幕，显示语言选择下拉菜单
**And** 显示 "开始下载" 按钮
**And** 页面响应式设计（移动端友好）

### Story 1.6: 实现队列系统和下载 Job

As a 系统,
I want 使用队列处理下载任务,
So that 下载在后台异步执行，不阻塞用户界面。

**Acceptance Criteria:**

**Given** 用户点击 "开始下载" 按钮
**When** 系统接收下载请求
**Then** 创建 `DownloadJob` 并推入 Redis 队列
**And** 立即返回下载任务 ID 给用户（≤ 1秒）
**And** 队列 worker 处理下载任务
**And** 调用 yt-dlp 执行实际下载
**And** 下载任务记录保存到 `download_tasks` 表（任务 ID、用户 IP、状态、创建时间）

**Given** 下载失败（yt-dlp 错误）
**When** 任务执行失败
**Then** 记录错误原因到 `download_tasks` 表
**And** 更新任务状态为 "failed"

### Story 1.7: 集成 Laravel Reverb 实现实时进度更新

As a 用户,
I want 看到下载的实时进度,
So that 我知道下载正在进行以及预计完成时间。

**Acceptance Criteria:**

**Given** 下载任务已创建
**When** 用户在下载进度页面
**Then** 前端通过 Laravel Echo 监听 `download.{id}` 频道
**And** 后端队列 Job 每 500ms 广播 `DownloadProgress` 事件
**And** 事件包含：status, percentage, eta（预计剩余时间）
**And** 前端实时更新进度条和百分比显示

**Given** 下载完成
**When** 任务状态变为 "completed"
**Then** 广播 `DownloadCompleted` 事件
**And** 前端显示成功提示和下载链接

**Given** 下载失败
**When** 任务状态变为 "failed"
**Then** 广播 `DownloadFailed` 事件
**And** 前端显示友好的错误信息

**Given** WebSocket 连接失败
**When** 前端无法建立 WebSocket
**Then** 自动降级为轮询模式（每 2秒调用进度 API）

### Story 1.8: 实现文件流式传输和交付

As a 用户,
I want 下载完成后自动获取文件,
So that 我无需手动复制文件，视频直接下载到我的设备。

**Acceptance Criteria:**

**Given** 下载任务完成
**When** yt-dlp 下载视频到临时位置
**Then** 生成签名 URL 用于安全下载（有效期 1小时）
**And** 前端自动触发文件下载（浏览器下载对话框）
**And** 视频文件流式传输给用户（不在服务器存储）
**And** 传输完成后立即删除临时文件

**Given** 用户选择了字幕
**When** 下载完成
**Then** 字幕文件（.srt）也通过签名 URL 提供
**And** 用户同时获取视频文件和字幕文件

### Story 1.9: 实现并发控制

As a 系统管理员,
I want 控制系统并发下载数量,
So that 系统资源不会被过度消耗。

**Acceptance Criteria:**

**Given** 系统配置了并发限制
**When** 已有 10 个下载任务在执行
**Then** 新的下载请求进入队列等待
**And** 显示提示："当前系统繁忙，您的下载已加入队列"

**Given** 单个用户（匿名按 IP 识别）
**When** 用户已有 1 个下载任务在执行
**Then** 新的下载请求被拒绝
**And** 显示提示："您已有一个下载任务正在进行，请等待完成"

### Story 1.10: 创建基础管理员访问

As a 管理员（jyu）,
I want 通过简单的认证访问下载功能,
So that 我可以在 Phase 1 本地环境中使用工具。

**Acceptance Criteria:**

**Given** 应用启动时
**When** 执行数据库 Seeder
**Then** 创建一个硬编码的管理员账号（email: admin@example.com, is_admin: true）
**And** 管理员可以登录（使用 Fortify 提供的登录页面）

**Given** 管理员已登录
**When** 管理员访问下载页面
**Then** 页面不显示任何广告占位符
**And** 没有配额限制提示
**And** 管理员可以无限制地下载视频

## Epic 2: User System & Authentication

**目标：** 访客可以注册账号，注册用户可以登录使用服务，系统能区分匿名用户、注册用户和管理员

**覆盖的 FRs：** FR1, FR2, FR3, FR4

### Story 2.1: 配置 Laravel Fortify 认证系统

As a 开发者,
I want 配置 Laravel Fortify 作为认证系统,
So that 项目具备完整的用户注册、登录、密码重置功能。

**Acceptance Criteria:**

**Given** 项目已初始化并安装 Fortify
**When** 配置 Fortify 服务
**Then** 启用 registration、login、password reset 功能
**And** 配置使用 session-based 认证
**And** 密码使用 bcrypt 哈希存储（满足 NFR7）
**And** Fortify 路由已发布（/register, /login, /logout）
**And** 认证视图使用 Blade + Livewire + Flux UI

### Story 2.2: 实现用户注册和邮箱验证

As a 访客,
I want 注册账号并验证邮箱,
So that 我可以使用注册用户的配额和功能。

**Acceptance Criteria:**

**Given** 访客在注册页面
**When** 填写邮箱和密码（最少 8 个字符）
**Then** 系统创建 `users` 表记录（id, email, password, is_admin, email_verified_at, created_at）
**And** 发送邮箱验证邮件
**And** 显示提示："请检查邮箱并点击验证链接"

**Given** 用户收到验证邮件
**When** 点击验证链接
**Then** `email_verified_at` 字段更新为当前时间
**And** 用户被重定向到登录页面
**And** 显示成功提示："邮箱验证成功，请登录"

**Given** 用户未验证邮箱
**When** 尝试访问下载功能
**Then** 显示提示："请先验证邮箱"（满足 NFR16）
**And** 阻止访问下载功能

### Story 2.3: 实现用户登录和会话管理

As a 注册用户,
I want 使用邮箱和密码登录,
So that 我可以使用我的账号下载视频。

**Acceptance Criteria:**

**Given** 用户已注册并验证邮箱
**When** 在登录页面输入正确的邮箱和密码
**Then** 系统创建 session
**And** 用户被重定向到下载页面
**And** 显示欢迎信息："欢迎回来，{email}！"

**Given** 用户输入错误的密码
**When** 提交登录表单
**Then** 显示错误提示："邮箱或密码错误"
**And** 不泄露具体是邮箱还是密码错误（安全考虑）

**Given** 已登录用户
**When** 点击登出按钮
**Then** 清除 session
**And** 重定向到首页

### Story 2.4: 实现匿名用户访问和角色区分

As a 访客,
I want 无需注册即可下载视频,
So that 我可以快速尝试服务。

**Acceptance Criteria:**

**Given** 未登录用户访问下载页面
**When** 页面加载
**Then** 显示提示："匿名用户可下载 3 部视频，注册后每天可下载 10 部"
**And** 用户可以正常使用下载功能（受匿名配额限制）

**Given** 系统需要识别用户类型
**When** 检查当前用户
**Then** 如果未登录，识别为 "匿名用户"（通过 IP 跟踪）
**And** 如果已登录且 is_admin = false，识别为 "注册用户"
**And** 如果已登录且 is_admin = true，识别为 "管理员"
**And** 创建 `user_sessions` 表记录匿名用户会话（id, ip_address, user_agent, created_at）

## Epic 3: Quota Management & Rate Limiting

**目标：** 系统可以控制不同用户的使用量，匿名和注册用户有不同的下载配额，用户可以查看剩余配额

**覆盖的 FRs：** FR18, FR19, FR20, FR21

### Story 3.1: 实现匿名用户配额限制

As a 系统,
I want 限制匿名用户的下载次数,
So that 防止滥用并鼓励用户注册。

**Acceptance Criteria:**

**Given** 匿名用户访问下载功能
**When** 检查配额
**Then** 系统通过 IP 地址识别匿名用户
**And** 从 `download_quotas` 表查询今日下载次数（ip_address, date, count）
**And** 如果 count < 3，允许下载
**And** 如果 count ≥ 3，拒绝下载并显示："今日配额已用完（3/3），请明天再试或注册账号"

**Given** 匿名用户成功下载
**When** 下载任务创建
**Then** `download_quotas` 表记录 +1
**And** 如果今日记录不存在，创建新记录（ip_address, date, count=1）

### Story 3.2: 实现注册用户配额限制

As a 系统,
I want 限制注册用户的每小时和每日下载次数,
So that 平衡资源使用和用户体验。

**Acceptance Criteria:**

**Given** 注册用户访问下载功能
**When** 检查配额
**Then** 从 `download_quotas` 表查询用户今日和当前小时下载次数（user_id, date, hour, hourly_count, daily_count）
**And** 如果 hourly_count < 5 且 daily_count < 10，允许下载
**And** 如果 hourly_count ≥ 5，拒绝下载并显示："本小时配额已用完（5/5），请 {下一小时} 后再试"
**And** 如果 daily_count ≥ 10，拒绝下载并显示："今日配额已用完（10/10），明天 00:00 重置"

**Given** 注册用户成功下载
**When** 下载任务创建
**Then** 更新 `download_quotas` 表：hourly_count +1, daily_count +1

**Given** 管理员用户
**When** 检查配额
**Then** 跳过配额检查，无限制下载

### Story 3.3: 实现配额显示 UI

As a 用户,
I want 查看我的剩余配额,
So that 我知道今天还能下载多少次。

**Acceptance Criteria:**

**Given** 匿名用户在下载页面
**When** 页面加载
**Then** 显示："今日剩余配额：{3-已用} / 3"
**And** 如果配额用尽，显示红色提示："今日配额已用完"

**Given** 注册用户在下载页面
**When** 页面加载
**Then** 显示："今日配额：{10-已用} / 10"
**And** 显示："本小时配额：{5-已用} / 5"
**And** 配额信息实时更新（每次下载后刷新）

**Given** 管理员在下载页面
**When** 页面加载
**Then** 不显示配额信息（无限制）

### Story 3.4: 实现配额自动重置

As a 系统,
I want 自动重置用户配额,
So that 用户可以在新的时间周期继续使用服务。

**Acceptance Criteria:**

**Given** 每天 00:00
**When** 配额重置任务执行
**Then** 所有 `download_quotas` 表中 date != 今日 的记录 daily_count 重置为 0
**And** 或删除旧记录并在首次下载时创建新记录

**Given** 每小时整点
**When** 配额重置任务执行
**Then** 所有 `download_quotas` 表中 hour != 当前小时 的记录 hourly_count 重置为 0
**And** 使用 Laravel Scheduler 配置定时任务

## Epic 4: Abuse Prevention & Security Hardening

**目标：** 系统可以防止滥用和自动化攻击，保护服务稳定性和资源

**覆盖的 FRs：** FR31, FR32

### Story 4.1: 实现 User-Agent 检测

As a 系统,
I want 检测并阻止非浏览器请求,
So that 防止自动化脚本滥用服务。

**Acceptance Criteria:**

**Given** 用户发起下载请求
**When** 系统检查 User-Agent
**Then** 如果 User-Agent 包含 "python", "curl", "wget", "bot"（不区分大小写）
**And** 拒绝请求并返回 403 Forbidden
**And** 显示错误："不支持的客户端，请使用浏览器访问"

**Given** 管理员后台
**When** 管理员访问安全设置
**Then** 可以开启/关闭 User-Agent 检测功能（配置存储到 `system_settings` 表）
**And** 可以自定义黑名单关键词

### Story 4.2: 集成 CAPTCHA 验证

As a 系统,
I want 在注册时要求 CAPTCHA 验证,
So that 防止批量注册机器人账号。

**Acceptance Criteria:**

**Given** 访客在注册页面
**When** 填写注册表单
**Then** 显示 Google reCAPTCHA v2 或 hCaptcha 验证
**And** 提交表单时验证 CAPTCHA token
**And** 如果 CAPTCHA 验证失败，拒绝注册并显示："验证失败，请重试"

**Given** CAPTCHA 验证失败 5 次（同一 IP）
**When** 检测到异常行为
**Then** 临时封锁该 IP 24 小时（记录到 `ip_blocks` 表：ip_address, blocked_until, reason）

### Story 4.3: 实现 IP 关联限制

As a 系统,
I want 限制同一 IP 的注册账号数量,
So that 防止批量注册绕过配额限制。

**Acceptance Criteria:**

**Given** 用户尝试注册
**When** 系统检查该 IP 的注册历史
**Then** 查询 `users` 表中过去 24 小时内该 IP 注册的账号数量（通过 `user_sessions` 关联）
**And** 如果数量 ≥ 3，拒绝注册
**And** 显示："该 IP 今日注册次数已达上限，请明天再试"

**Given** 超过 IP 注册限制
**When** 继续尝试注册
**Then** 记录异常行为到 `abuse_logs` 表（ip_address, action, timestamp）

### Story 4.4: 实现异常行为监控和告警

As a 系统管理员,
I want 监控异常下载行为并接收告警,
So that 可以及时处理滥用行为。

**Acceptance Criteria:**

**Given** 系统监控下载行为
**When** 单个用户（或 IP）1 小时内尝试下载超过 15 次
**Then** 记录到 `abuse_logs` 表（user_id/ip_address, action="excessive_downloads", count, timestamp）
**And** 发送告警邮件给管理员："用户 {id} 或 IP {address} 疑似滥用，1小时内尝试 {count} 次下载"

**Given** 系统下载失败率 > 30%（过去 1 小时）
**When** 检测到异常失败率
**Then** 发送告警邮件给管理员："系统下载失败率异常（{percentage}%），可能 YouTube 更新或服务异常"

**Given** 单个 IP 1 小时内请求 > 100 次
**When** 检测到疑似攻击
**Then** 发送告警邮件："IP {address} 疑似攻击，1小时 {count} 次请求"
**And** 自动临时封锁该 IP 24 小时

## Epic 5: Admin Operations & Monitoring Dashboard

**目标：** 管理员可以监控系统运营状态，查看统计信息，管理用户，处理滥用行为

**覆盖的 FRs：** FR24, FR25, FR26, FR27, FR28, FR29, FR30, FR35

### Story 5.1: 创建管理员仪表板框架

As a 管理员,
I want 访问专用的管理后台,
So that 我可以监控和管理系统。

**Acceptance Criteria:**

**Given** 管理员已登录
**When** 访问 `/admin` 路由
**Then** 显示管理后台首页（仪表板）
**And** 使用 Flux UI 组件库构建 UI
**And** 包含侧边栏导航：仪表板、用户管理、系统状态、错误日志、设置

**Given** 非管理员用户
**When** 尝试访问 `/admin`
**Then** 返回 403 Forbidden
**And** 使用 Laravel Policy 或 Gate 进行权限验证

### Story 5.2: 实现今日统计展示

As a 管理员,
I want 查看今日运营统计,
So that 我了解系统的使用情况。

**Acceptance Criteria:**

**Given** 管理员在仪表板
**When** 页面加载
**Then** 显示今日下载次数（从 `download_tasks` 表统计）
**And** 显示新注册用户数（从 `users` 表统计）
**And** 显示活跃用户数（今日有下载行为的用户）
**And** 显示匿名下载次数 vs 注册用户下载次数
**And** 数据每 5 秒通过 WebSocket 自动更新（可选）

### Story 5.3: 实现系统状态监控

As a 管理员,
I want 查看系统运行状态,
So that 我可以及时发现性能问题。

**Acceptance Criteria:**

**Given** 管理员在仪表板
**When** 查看系统状态面板
**Then** 显示服务器 CPU 使用率（通过 `sys_getloadavg()` 获取）
**And** 显示内存使用情况（已用 / 总量）
**And** 显示当前下载队列长度（从 Redis 查询）
**And** 显示 yt-dlp 版本信息
**And** 显示数据库连接状态（正常 / 异常）
**And** 显示 Redis 连接状态（正常 / 异常）
**And** 状态信息每 5 秒自动刷新

### Story 5.4: 实现收入和广告统计

As a 管理员,
I want 查看广告展示和收入预估,
So that 我了解变现效果。

**Acceptance Criteria:**

**Given** 管理员在仪表板
**When** 查看收入统计面板
**Then** 显示今日广告展示次数（从 `ad_impressions` 表统计）
**And** 显示预估收入（广告展示次数 × 预估单价）
**And** 显示本月累计展示次数和收入
**And** 可以查看历史数据（按日期筛选）
**And** 创建 `ad_impressions` 表（id, user_id/ip_address, ad_unit, timestamp）

### Story 5.5: 实现错误日志查看

As a 管理员,
I want 查看系统错误日志,
So that 我可以排查问题。

**Acceptance Criteria:**

**Given** 管理员在错误日志页面
**When** 页面加载
**Then** 显示最近 100 条错误日志（从 `error_logs` 表）
**And** 每条日志包含：时间、错误类型、错误信息、用户 ID/IP、任务 ID
**And** 可以按日期范围筛选
**And** 可以按错误类型筛选（yt-dlp 错误、系统错误、网络错误）
**And** 显示过去 1 小时的下载成功率和失败率
**And** 创建 `error_logs` 表（id, error_type, message, user_id, ip_address, task_id, timestamp）

### Story 5.6: 实现用户详情查看

As a 管理员,
I want 查看用户详细信息和下载历史,
So that 我可以识别滥用行为。

**Acceptance Criteria:**

**Given** 管理员在用户管理页面
**When** 搜索或点击用户
**Then** 显示用户详情：ID、邮箱、注册时间、是否验证、是否管理员
**And** 显示用户的 IP 地址历史（从 `user_sessions` 表）
**And** 显示用户的 User-Agent 历史
**And** 显示用户的下载记录（最近 50 条）：时间、视频标题、状态、IP
**And** 显示用户的配额使用情况

### Story 5.7: 实现封锁用户账号

As a 管理员,
I want 封锁滥用的用户账号,
So that 阻止其继续使用服务。

**Acceptance Criteria:**

**Given** 管理员在用户详情页面
**When** 点击 "封锁用户" 按钮
**Then** 弹出确认对话框："确定要封锁此用户吗？封锁后用户无法登录和使用服务。"
**And** 确认后，更新 `users` 表：is_blocked = true, blocked_at, blocked_reason
**And** 该用户所有 session 立即失效
**And** 该用户尝试登录时显示："您的账号已被封锁，如有疑问请联系管理员"

**Given** 被封锁的用户
**When** 尝试使用任何功能
**Then** 系统拒绝所有请求并显示封锁提示

### Story 5.8: 实现封锁 IP 地址

As a 管理员,
I want 封锁滥用的 IP 地址,
So that 阻止该 IP 的所有访问。

**Acceptance Criteria:**

**Given** 管理员在用户详情或滥用日志页面
**When** 点击 "封锁 IP" 按钮
**Then** 弹出封锁时长选择："临时封锁（24小时）" 或 "永久封锁"
**And** 输入封锁原因（必填）
**And** 确认后，创建或更新 `ip_blocks` 表记录（ip_address, blocked_until, reason, created_by）

**Given** 被封锁的 IP 访问网站
**When** 发起任何请求
**Then** 返回 403 Forbidden
**And** 显示："您的 IP 已被封锁，原因：{reason}，解封时间：{blocked_until}"

**Given** 临时封锁到期
**When** 当前时间 > blocked_until
**Then** 自动解除封锁（通过中间件检查）

## Epic 6: Monetization & Legal Framework

**目标：** 系统可以通过广告变现并满足法律合规要求，用户理解条款和责任

**覆盖的 FRs：** FR22, FR23, FR33, FR34

### Story 6.1: 集成 Google AdSense

As a 系统,
I want 集成 Google AdSense 广告,
So that 通过广告展示实现变现。

**Acceptance Criteria:**

**Given** AdSense 账号已批准
**When** 配置广告代码
**Then** 在 Blade 模板中添加 AdSense 脚本（异步加载）
**And** 在以下位置放置广告单元：顶部横幅、侧边栏、下载页面
**And** 广告代码存储在配置文件中（便于更换）
**And** 使用 `@production` 指令仅在生产环境显示广告

### Story 6.2: 实现基于角色的广告显示逻辑

As a 用户,
I want 根据我的角色看到或不看到广告,
So that 管理员享受无广告特权，其他用户支持网站运营。

**Acceptance Criteria:**

**Given** 匿名用户或注册用户访问页面
**When** 页面加载
**Then** 显示所有配置的广告位（顶部、侧边栏、页面内）
**And** 记录广告展示到 `ad_impressions` 表

**Given** 管理员访问页面
**When** 页面加载
**Then** 不显示任何广告
**And** 通过 `@if(!auth()->user()?->is_admin)` 控制广告显示

**Given** 广告脚本加载失败（AdBlock）
**When** 前端检测到广告被拦截
**Then** 显示友好提示："我们依靠广告维持服务，请考虑将我们加入白名单"（可选）

### Story 6.3: 创建使用条款和免责声明页面

As a 用户,
I want 阅读使用条款和免责声明,
So that 我了解服务的法律约束和责任。

**Acceptance Criteria:**

**Given** 用户访问网站
**When** 点击页脚的 "使用条款" 链接
**Then** 显示使用条款页面（`/terms`）
**And** 包含以下内容：服务仅供个人学习和研究使用、用户自行承担使用责任和法律风险、禁止商业用途声明、服务提供方保留随时终止服务的权利、不保证服务可用性和下载成功率

**Given** 用户访问网站
**When** 点击页脚的 "免责声明" 链接
**Then** 显示免责声明页面（`/disclaimer`）
**And** 包含："本工具仅供个人学习和研究使用，用户需自行承担使用责任。请遵守当地法律法规和 YouTube 服务条款。"
**And** 首页也显示简短免责声明

### Story 6.4: 实现用户协议确认流程

As a 系统,
I want 要求用户在注册和首次使用时同意条款,
So that 确保用户知晓并接受使用条款。

**Acceptance Criteria:**

**Given** 用户在注册页面
**When** 填写注册表单
**Then** 显示复选框："我已阅读并同意 [使用条款] 和 [免责声明]"
**And** 复选框必须勾选才能提交
**And** 提交时记录 `users` 表：terms_accepted_at = 当前时间

**Given** 匿名用户首次访问下载页面
**When** 页面加载
**Then** 显示模态对话框：使用条款摘要 + "同意并继续" 按钮
**And** 同意后记录到 `user_sessions` 表或 cookie
**And** 同一 IP 24 小时内不再显示

## Epic 7: Data Lifecycle & System Maintenance

**目标：** 系统可以自动维护数据（保留和清理），管理员可以发布公告和管理工具版本

**覆盖的 FRs：** FR36, FR37, FR38, FR39

### Story 7.1: 实现系统公告功能

As a 管理员,
I want 发布系统公告,
So that 向用户通知维护、更新或重要信息。

**Acceptance Criteria:**

**Given** 管理员在管理后台
**When** 访问 "系统公告" 页面
**Then** 显示当前有效的公告列表
**And** 可以创建新公告：标题、内容、类型（info/warning/success）、生效时间、失效时间
**And** 创建后保存到 `system_announcements` 表（id, title, content, type, starts_at, ends_at, is_active）

**Given** 存在有效公告
**When** 用户访问网站
**Then** 在页面顶部显示公告横幅
**And** 公告样式根据类型显示（蓝色info / 黄色warning / 绿色success）
**And** 用户可以关闭公告（关闭状态存储在 localStorage）

**Given** 公告过期
**When** 当前时间 > ends_at
**Then** 不再显示该公告

### Story 7.2: 配置数据保留策略

As a 系统管理员,
I want 配置数据保留策略,
So that 系统自动清理过期数据，保护用户隐私。

**Acceptance Criteria:**

**Given** 管理员在管理后台
**When** 访问 "数据保留设置" 页面
**Then** 可以配置以下策略（保存到 `system_settings` 表）：匿名用户下载记录保留24小时、注册用户下载记录保留90天、IP记录保留30天、错误日志保留30天、用户会话记录保留30天
**And** 显示当前各类数据的存储量
**And** 保存配置后显示成功提示

### Story 7.3: 实现下载记录自动清理

As a 系统,
I want 自动清理过期的下载记录,
So that 遵守数据保留策略，释放存储空间。

**Acceptance Criteria:**

**Given** 配置了数据保留策略
**When** 每日凌晨 2:00 执行清理任务（Laravel Scheduler）
**Then** 删除 `download_tasks` 表中：匿名用户（user_id IS NULL）且 created_at < 24 小时前 的记录、注册用户且 created_at < 90 天前 的记录
**And** 记录清理日志到 `cleanup_logs` 表（date, table_name, records_deleted）
**And** 如果清理失败，发送告警邮件给管理员

### Story 7.4: 实现日志和 IP 记录自动清理

As a 系统,
I want 自动清理过期的日志和 IP 记录,
So that 遵守数据保留策略。

**Acceptance Criteria:**

**Given** 配置了数据保留策略
**When** 每日凌晨 2:00 执行清理任务
**Then** 删除 `error_logs` 表中 timestamp < 30 天前 的记录
**And** 删除 `user_sessions` 表中 created_at < 30 天前 的记录
**And** 删除 `download_quotas` 表中 date < 30 天前 的记录
**And** 删除 `ip_blocks` 表中 blocked_until < 当前时间 且 type != 'permanent' 的记录
**And** **不删除** 被封锁期间的 IP 记录（blocked_until > 当前时间）
**And** 记录清理结果到 `cleanup_logs` 表

### Story 7.5: 实现 yt-dlp 版本管理

As a 管理员,
I want 查看和更新 yt-dlp 版本,
So that 确保下载功能正常运行。

**Acceptance Criteria:**

**Given** 管理员在管理后台
**When** 访问 "系统维护" 页面
**Then** 显示当前 yt-dlp 版本号（通过 `yt-dlp --version` 获取）
**And** 显示上次检查更新时间
**And** 提供 "检查更新" 按钮

**Given** 点击 "检查更新"
**When** 系统执行检查
**Then** 对比 GitHub 最新 release 版本
**And** 如果有新版本，显示更新提示和版本说明
**And** 提供手动更新说明（SSH 到服务器执行 `pip install -U yt-dlp`）
**And** **不自动更新**，需要管理员手动操作（安全考虑）
