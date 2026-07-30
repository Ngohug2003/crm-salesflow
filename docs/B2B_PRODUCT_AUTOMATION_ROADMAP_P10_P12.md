# SalesFlow CRM — Product & Automation Specification P10–P12

> Tài liệu này là đặc tả triển khai cho các tính năng B2B nâng cao sau P1–P9. Nguồn yêu cầu chuẩn vẫn là [`requirements.md`](requirements.md). Khi có xung đột, áp dụng thứ tự ưu tiên trong mục 1.1 của requirements.

## 1. Mục tiêu

Lộ trình P10–P12 đưa SalesFlow từ CRM quản lý dữ liệu thành nền tảng điều hành bán hàng B2B có khả năng:

- Tự động phân bổ, theo dõi và thúc đẩy Lead/Opportunity.
- Chuẩn hóa playbook, báo giá, phê duyệt và forecast.
- Hạn chế Lead hoặc thương vụ bị bỏ quên.
- Giảm nhập liệu và thao tác lặp của Sales.
- Cung cấp cho Manager khả năng kiểm soát doanh thu, rủi ro và hiệu suất.
- Mở rộng an toàn sang khách hàng bên ngoài, hệ thống tích hợp và AI có kiểm soát.

## 2. Nguyên tắc và ranh giới

### 2.1 Quyết định kỹ thuật bắt buộc

- Giữ PostgreSQL `BIGINT` tự tăng theo `DEC-001`.
- Múi giờ nghiệp vụ `Asia/Ho_Chi_Minh` theo `DEC-002`.
- UI tiếng Việt, Flux UI Free, Livewire, Blade, Alpine và Tailwind.
- Giữ cây thư mục technical-layer hiện tại.
- Không cài package nếu Laravel/core và package hiện có đã đáp ứng.
- Mọi mutation phải authorize ở backend và áp data scope trước khi query.
- Workflow chạy nền phải idempotent, có retry/backoff và phát side effect sau transaction commit.
- Audit phải có actor/system actor, request ID/correlation ID, old/new values và không lộ secret.
- Public token, OAuth token, API key và webhook secret phải mã hóa hoặc hash phù hợp; không lưu plain text có thể đọc lại nếu không cần.

### 2.2 Quality gate theo giai đoạn

- Hoàn tất `P9-02 Security hardening` trước `P10-05`, `P11-05`, `P12-02`, `P12-03`, `P12-04` và `P12-05`.
- Hoàn tất `P9-03 Performance và database indexes` trước khi bật automation quét dữ liệu lớn.
- `P9-05 CI` và `P9-06 Production deployment` là điều kiện đưa integration/public feature lên production.
- Feature AI chỉ được bật theo cấu hình, có consent, masking và retention policy.

### 2.3 Quy trình triển khai

1. Làm đúng thứ tự dependency trong bảng roadmap.
2. Mỗi feature dùng một branch.
3. Trước khi code, trình bày mục đích, phạm vi, rủi ro, requirement ID và checklist nghiệm thu.
4. Mỗi feature có migration/domain/service/repository/policy/UI/test/docs đúng nhu cầu.
5. Chạy targeted tests, Pint, PHPStan và Vite build.
6. Cập nhật `docs/PROJECT_PHASES.md`, đề xuất commit và dừng cho chủ dự án kiểm thử.

## 3. Roadmap tổng thể

| Thứ tự | Mã | Feature | Branch đề xuất | Phụ thuộc chính | Giá trị | Trạng thái |
|---:|---|---|---|---|---|---|
| 0 | P10-00 | Danh mục đơn vị hành chính Việt Nam | `develop` | P4-07, P8-03 | Chuẩn hóa địa bàn cho Lead, Company, Contact và routing | Chờ kiểm thử thủ công |
| 1 | P10-01 | Smart Lead Auto-Routing & SLA Reassign | `feature/p10-01-lead-auto-routing` | P3-10, P8-06, P9-03 | Giảm chia Lead thủ công và Lead bị ngâm | Chờ kiểm thử thủ công |
| 2 | P10-02 | PDF Quote Builder & Discount Approval | `feature/p10-02-pdf-quote-approval` | P5-X01, P5-X02, P8-06 | Chuẩn hóa báo giá và kiểm soát chiết khấu | Chờ duyệt |
| 3 | P10-03 | Stage Automation & Sales Playbook | `feature/p10-03-stage-playbook` | P5-X03, P6-07, P8-06 | Sale luôn biết bước tiếp theo | Chờ kiểm thử thủ công |
| 4 | P10-04 | At-Risk Deal Detection | `feature/p10-04-at-risk-deals` | P10-03, P7-X04 | Phát hiện thương vụ có nguy cơ sớm | Chờ duyệt |
| 5 | P10-05 | Public Quote Link & Customer Acceptance | `feature/p10-05-public-quote-acceptance` | P10-02, P9-02 | Khách xem/chấp thuận báo giá trực tuyến | Chờ duyệt |
| 6 | P11-01 | Product Catalog & Price Books | `feature/p11-01-product-price-books` | P5-X01, P10-02 | Giảm nhập dòng sản phẩm và sai giá | Chờ duyệt |
| 7 | P11-02 | Buying Committee & Stakeholder Map | `feature/p11-02-buying-committee` | P4-X01, P5-09 | Quản lý người quyết định trong deal B2B | Chờ duyệt |
| 8 | P11-03 | Contract & Renewal Management | `feature/p11-03-contract-renewals` | P10-05, P11-01 | Theo dõi hợp đồng và tự tạo cơ hội gia hạn | Chờ duyệt |
| 9 | P11-04 | Manager Forecast Review | `feature/p11-04-forecast-review` | P5-X04, P10-04, P7-X01 | Chốt forecast có giải trình và lịch sử | Chờ duyệt |
| 10 | P11-05 | Email & Calendar Integration | `feature/p11-05-email-calendar-integration` | P9-02, P6-06 | Tự đồng bộ email/cuộc họp vào CRM | Chờ duyệt |
| 11 | P12-01 | Workflow Automation Builder | `feature/p12-01-workflow-builder` | P10-01..P10-04, P9-03 | Admin tự cấu hình trigger-condition-action | Chờ duyệt |
| 12 | P12-02 | Webhook & API Integration Center | `feature/p12-02-integration-center` | P9-02, P9-05 | Kết nối ERP, website và hệ thống ngoài | Chờ duyệt |
| 13 | P12-03 | Customer Portal | `feature/p12-03-customer-portal` | P10-05, P11-03, P9-02 | Không gian hợp tác với khách hàng | Chờ duyệt |
| 14 | P12-04 | Data Enrichment | `feature/p12-04-data-enrichment` | P12-02, P4-X02 | Giảm nhập Company/Contact thủ công | Chờ duyệt |
| 15 | P12-05 | AI Meeting Summary & Next Best Action | `feature/p12-05-ai-sales-assistant` | P11-05, P12-01, P9-02 | Tóm tắt tương tác và đề xuất hành động | Chờ duyệt |

## 4. P10 — Sales Automation và Revenue Governance

### P10-00 — Danh mục đơn vị hành chính Việt Nam

Feature nền tảng này nhập danh mục hai cấp gồm 34 Tỉnh/Thành phố và 3.321 Phường/Xã từ dữ liệu JSON đã được chủ dự án cung cấp. Lead, Company và Contact dùng khóa ngoại `BIGINT` cho địa bàn; form sử dụng select phụ thuộc thay cho nhập tự do, đồng thời vẫn duy trì cột text cũ để tương thích báo cáo/import hiện hành.

- Dữ liệu nguồn được lưu tại `database/data/vietnam_administrative_units.json`.
- Import/seed idempotent bằng `administrative-units:import`.
- Backend bắt buộc Phường/Xã thuộc đúng Tỉnh/Thành phố.
- Backfill chỉ tự gán khi tên cũ khớp rõ ràng; dữ liệu mơ hồ được giữ nguyên để xử lý thủ công.
- Checkpoint: `docs/checkpoints/P10/P10-00_VIETNAM_ADMINISTRATIVE_UNITS.md`.

### P10-01 — Smart Lead Auto-Routing & SLA Reassign

#### Mục tiêu kinh doanh

Lead mới được giao đúng người nhanh chóng, công bằng và có thể giải thích. Lead quá hạn phản hồi được nhắc, thu hồi hoặc chuyển giao theo chính sách thay vì phụ thuộc Manager kiểm tra thủ công.

#### Vai trò

- Admin IT/Super Admin: cấu hình rule và lịch chạy.
- Sales Manager: cấu hình phạm vi phòng ban, xem hàng đợi và override.
- Sales: nhận Lead, xác nhận tiếp nhận và thực hiện first touch.

#### Phạm vi

- Routing rule theo ưu tiên: địa bàn, nguồn Lead, giá trị dự kiến, phòng ban và round-robin.
- Chỉ chọn user active, verified, không locked, đúng role/phòng ban và chưa vượt capacity.
- SLA first response theo giờ làm việc, timezone Việt Nam và calendar cấu hình.
- Reminder trước hạn; reassign hoặc trả về pool khi quá hạn.
- Lưu routing decision, ứng viên đã xét, rule khớp và lý do fallback.
- Notification database/broadcast/email theo preference.
- Dashboard hàng đợi Lead chưa phân công, gần quá hạn và đã quá hạn.

#### Ngoài phạm vi

- AI phân tích năng lực nhân viên.
- Đồng bộ chấm công.
- Tối ưu tuyến đường hoặc call-center dialer.

#### Luồng chính

```text
LeadCreated/LeadImported
→ chuẩn hóa dữ liệu routing
→ lấy rule active theo priority
→ resolve danh sách ứng viên đúng scope
→ khóa routing cursor/candidate cần thiết
→ chọn owner
→ lưu assignment + SLA deadline trong transaction
→ after commit gửi notification và audit
→ scheduler kiểm tra first touch
→ reminder/reassign/escalate idempotent
```

#### Quy tắc và edge case

- Một Lead chỉ có một routing execution thành công cho cùng trigger.
- Import retry không được phân công lại Lead đã xử lý.
- Không có ứng viên hợp lệ thì đưa vào unassigned pool và báo Manager.
- Manual assignment có thể khóa auto-reassign trong thời gian cấu hình.
- First touch hợp lệ phải là Activity/Note thuộc đúng Lead và loại được cấu hình.
- User bị khóa/nghỉ việc phải bị loại khỏi routing ngay.
- Reassign không được chọn lại owner vừa vi phạm SLA nếu vẫn còn ứng viên khác.

#### Dữ liệu dự kiến

- `lead_routing_rules`
- `lead_routing_rule_conditions`
- `lead_routing_executions`
- `lead_routing_cursors`
- Bổ sung SLA fields phù hợp vào `leads` hoặc bảng execution riêng.
- Tái sử dụng `lead_assignment_histories`, notifications và activity log.

#### Authorization, audit và notification

- Chỉ Admin IT/Super Admin cấu hình toàn hệ thống.
- Manager chỉ xem/override rule và Lead trong department scope.
- Audit create/update/enable/disable rule, routing decision, manual override và reassignment.
- Notification không gửi metadata Lead ngoài data scope.

#### Test bắt buộc

- Round-robin tuần tự và concurrent.
- Territory/source/value routing.
- Fallback khi không có ứng viên.
- SLA theo giờ làm việc và timezone.
- Idempotency khi scheduler/job retry.
- Locked/inactive user bị loại.
- Department/data-scope và audit.

#### Nghiệm thu thủ công

- [ ] Tạo nhiều Lead liên tiếp và thấy owner được chia đúng round-robin.
- [ ] Lead Hà Nội/TP.HCM đi đúng team theo rule.
- [ ] Lead không khớp rule vào pool và Manager được thông báo.
- [ ] Lead quá SLA được reminder/reassign đúng một lần.
- [ ] Assignment history thể hiện rõ auto routing, SLA và manual override.

---

### P10-02 — PDF Quote Builder & Discount Approval

#### Mục tiêu kinh doanh

Sale tạo báo giá chuẩn thương hiệu nhanh, trong khi doanh nghiệp kiểm soát chiết khấu và biên lợi nhuận trước khi tài liệu được phát hành.

#### Vai trò

- Sales: tạo draft, gửi duyệt và phát hành khi đủ quyền.
- Sales Manager: duyệt mức chiết khấu trong hạn mức.
- Admin/Super Admin: duyệt mức cao, cấu hình approval matrix và branding.

#### Phạm vi

- Quote state machine: `draft → pending_approval → approved/rejected → issued → sent → accepted/declined/expired`.
- Approval matrix theo discount, margin, quote total, department hoặc product group.
- Snapshot dữ liệu khách hàng, line item, thuế, giá và điều khoản tại thời điểm phát hành.
- PDF thương hiệu gồm logo, thông tin pháp nhân, khách hàng, line items, VAT, tổng tiền bằng số/chữ và điều khoản.
- Version báo giá; sửa báo giá đã phát hành phải tạo version mới.
- Signed/authorized download, file private và expiration.
- Approval inbox và realtime notification.

#### Ngoài phạm vi

- Chữ ký số pháp lý.
- Trình kéo-thả PDF.
- Hóa đơn điện tử hoặc kế toán.

#### Luồng chính

```text
Draft Quote
→ tính subtotal/discount/tax/total/margin ở backend
→ resolve approval level
→ tự duyệt hoặc gửi approver
→ approve/reject có lý do
→ tạo immutable issued snapshot
→ queue render PDF
→ lưu private
→ cho phép gửi/tải theo policy
```

#### Quy tắc và edge case

- Dùng decimal, không dùng float.
- Quote pending/approved/issued không được sửa trực tiếp.
- Approver không được duyệt quote ngoài scope hoặc quote do chính mình tạo nếu policy yêu cầu phân tách nhiệm vụ.
- Thay line item/discount sau khi được duyệt làm approval cũ mất hiệu lực.
- Job render retry không tạo nhiều version/file.
- Logo hoặc font lỗi phải có fallback rõ ràng.

#### Dữ liệu dự kiến

- Mở rộng `quotes`, `quote_items`.
- `quote_approval_rules`
- `quote_approval_requests`
- `quote_approval_actions`
- `quote_versions`
- `quote_documents`
- Company branding/settings phù hợp.

#### Authorization, audit và notification

- Policy riêng cho create, submit, approve, reject, issue, download và void.
- Audit đầy đủ old/new, approver, lý do, version và request ID.
- Notification khi cần duyệt, được duyệt, bị từ chối, PDF sẵn sàng hoặc sắp hết hạn.

#### Test bắt buộc

- Approval threshold boundary 10%/20% hoặc cấu hình tương đương.
- Tính tiền, thuế, discount và rounding.
- SoD/authorization/data scope.
- Concurrent approval và stale version.
- Signed download, expired/tampered URL.
- Queue render idempotency.

#### Nghiệm thu thủ công

- [ ] Sale tạo quote từ Opportunity mà không nhập lại khách hàng.
- [ ] Discount vượt hạn mức chuyển đúng approver.
- [ ] Quote pending không sửa hoặc tải bản chính thức được.
- [ ] PDF tiếng Việt đúng font, logo, VAT, tổng tiền và điều khoản.
- [ ] Mọi lần duyệt/từ chối/phát hành có audit.

---

### P10-03 — Stage Automation & Sales Playbook

#### Mục tiêu kinh doanh

Chuẩn hóa cách đội Sales làm việc tại từng pipeline stage và tự tạo hành động tiếp theo, giảm việc Sale phải nhớ checklist hoặc Manager nhắc thủ công.

#### Phạm vi

- Playbook theo pipeline/stage gồm mục tiêu, câu hỏi qualification, required fields, checklist và tài liệu gợi ý.
- Automation template tạo Task/Activity/reminder khi vào stage.
- Exit criteria chặn chuyển stage nếu thiếu dữ liệu hoặc task bắt buộc.
- Due date tính theo business day/SLA.
- Template có version; Opportunity đã vào stage giữ snapshot phù hợp.
- Hiển thị progress playbook trong Opportunity detail/Kanban.

#### Ngoài phạm vi

- Workflow builder tổng quát; thuộc P12-01.
- AI tạo playbook.
- Tự động gửi email bên ngoài trong feature này.

#### Luồng chính

```text
OpportunityStageChanging
→ authorize và kiểm tra version conflict
→ validate required fields/exit criteria
→ chuyển stage + history trong transaction
→ after commit kích hoạt playbook version
→ tạo task/checklist/reminder idempotent
→ hiển thị next action
```

#### Dữ liệu dự kiến

- `sales_playbooks`
- `sales_playbook_steps`
- `stage_playbook_assignments`
- `opportunity_playbook_runs`
- `opportunity_playbook_step_runs`

#### Rule quan trọng

- Không tạo trùng task khi event/job retry.
- Reopen/re-enter stage áp policy `once`, `every_entry` hoặc `manual`.
- Xóa template không làm mất lịch sử run.
- Task tạo tự động vẫn tuân theo TaskPolicy và subject visibility.

#### Test và nghiệm thu

- [ ] Chuyển stage tạo đúng checklist/task/deadline một lần.
- [ ] Thiếu exit criteria thì bị chặn với lỗi tiếng Việt rõ ràng.
- [ ] Quay lại stage xử lý đúng repeat policy.
- [ ] Manager thấy tiến độ playbook của từng Opportunity.
- [ ] Viewer chỉ đọc, không hoàn tất step.

---

### P10-04 — At-Risk Deal Detection

#### Mục tiêu kinh doanh

Phát hiện sớm Opportunity có khả năng trượt mục tiêu để Sale xử lý và Manager can thiệp trước ngày chốt.

#### Tín hiệu rủi ro MVP

- Không có Activity trong số ngày cấu hình.
- Stage đứng yên quá lâu.
- Gần expected close date nhưng chưa có quote hoặc quote chưa duyệt.
- Không có contact/người quyết định.
- Task quan trọng quá hạn.
- Expected value cao nhưng dữ liệu qualification thiếu.
- Quote hết hạn hoặc bị từ chối.

#### Phạm vi

- Rule-based risk score `0–100`, level `low/medium/high/critical`.
- Scheduler tính lại theo chunk; event quan trọng có thể request recalculation.
- Hiển thị risk badge, lý do và recommended action.
- Manager risk dashboard theo department/owner/pipeline.
- Escalation notification có cooldown.
- Cho phép acknowledge/resolve nhưng không sửa lịch sử score.

#### Ngoài phạm vi

- Machine learning dự đoán win probability.
- Tự động đóng Lost.

#### Dữ liệu dự kiến

- `opportunity_risk_snapshots`
- `opportunity_risk_factors`
- `opportunity_risk_acknowledgements`
- Config/rule table chỉ tạo nếu cần UI quản trị; MVP có thể bắt đầu bằng config versioned.

#### Rule và bảo mật

- Score phải giải thích được bằng từng factor.
- Recalculation idempotent theo Opportunity + rule version + evaluation time bucket.
- Dashboard/report luôn áp data scope.
- Notification không lặp trong cooldown trừ khi level tăng.

#### Test và nghiệm thu

- [ ] Deal không tương tác hoặc quá hạn được tăng risk đúng rule.
- [ ] Hoàn thành action cần thiết làm risk giảm sau recalculation.
- [ ] Manager drill-down được từ chỉ số đến danh sách deal.
- [ ] Sales chỉ thấy deal thuộc owner scope.
- [ ] Notification không spam khi scheduler chạy lại.

---

### P10-05 — Public Quote Link & Customer Acceptance

#### Điều kiện bắt buộc

Hoàn tất `P9-02 Security hardening` trước khi triển khai endpoint public.

#### Mục tiêu kinh doanh

Khách hàng có thể xem, tải, chấp thuận, từ chối hoặc gửi câu hỏi về báo giá qua link an toàn mà không cần tài khoản CRM.

#### Phạm vi

- Public quote page responsive, branded và chỉ hiển thị issued snapshot.
- Token random đủ mạnh, lưu hash, có expiration, revoke và optional access code.
- Customer action: view, download, accept, decline và comment.
- Xác nhận tên/email/chức danh người thực hiện; lưu IP/user agent/timestamp.
- Sale nhận realtime/email notification.
- Acceptance tạo event và có thể chuyển Opportunity theo rule có xác nhận.

#### Ngoài phạm vi

- Chữ ký điện tử pháp lý.
- Thanh toán online.
- Portal đầy đủ; thuộc P12-03.

#### Luồng chính

```text
Sale phát hành link
→ hệ thống tạo token và gửi qua kênh được chọn
→ khách mở link
→ kiểm tra hash/expiry/revoke/rate limit
→ render issued snapshot
→ khách accept/decline/comment
→ transaction lưu response
→ after commit notify Sale và trigger workflow
```

#### Bảo mật và edge case

- Không dùng ID tuần tự làm public credential.
- Không tiết lộ sự tồn tại của quote khi token sai.
- Rate limit view/action; chống submit lặp.
- Link của version cũ bị revoke khi phát hành version mới theo policy.
- Không broadcast thông tin khách hàng lên public channel.

#### Dữ liệu dự kiến

- `quote_public_links`
- `quote_customer_responses`
- `quote_public_events`

#### Test và nghiệm thu

- [ ] Link hợp lệ xem đúng issued version; token sai/hết hạn/revoke bị từ chối.
- [ ] Khách accept/decline chỉ được một quyết định cuối theo rule.
- [ ] Refresh hoặc submit lặp không tạo nhiều response.
- [ ] Sale nhận thông báo và timeline Opportunity được cập nhật.
- [ ] Trang public không lộ navigation hoặc dữ liệu CRM nội bộ.

## 5. P11 — B2B Commercial Operations

### P11-01 — Product Catalog & Price Books

#### Mục tiêu kinh doanh

Chuẩn hóa sản phẩm/dịch vụ và bảng giá để Sale chọn nhanh, giảm nhập sai tên, SKU, đơn giá, thuế và discount.

#### Phạm vi

- Product/service catalog: SKU, tên, mô tả, đơn vị, giá chuẩn, VAT, trạng thái.
- Price book theo loại khách hàng, khu vực, tiền tệ hoặc thời gian hiệu lực.
- Price book entries có giá, min quantity và hiệu lực.
- Chọn sản phẩm vào Opportunity/Quote line items và snapshot dữ liệu.
- Import/export catalog có kiểm soát.

#### Ngoài phạm vi

- Tồn kho, kho vận, procurement.
- Đồng bộ ERP; thực hiện qua P12-02.
- Multi-currency conversion tự động ở MVP.

#### Dữ liệu dự kiến

- `products`
- `price_books`
- `price_book_entries`
- Bổ sung product snapshot reference vào opportunity/quote items.

#### Rule và test

- SKU unique theo active policy.
- Không sửa hồi tố issued quote.
- Price book hết hạn không dùng cho quote mới.
- Decimal/rounding và VAT chính xác.
- Data scope cho price book nội bộ; audit thay đổi giá.

#### Nghiệm thu

- [ ] Sale chọn price book và thêm sản phẩm mà không nhập lại giá.
- [ ] Customer segment khác nhau nhận đúng bảng giá.
- [ ] Thay giá catalog không làm đổi quote đã phát hành.
- [ ] Import catalog báo lỗi theo từng dòng.

---

### P11-02 — Buying Committee & Stakeholder Map

#### Mục tiêu kinh doanh

Giúp Sale quản lý đầy đủ những người tham gia quyết định trong thương vụ B2B, thay vì chỉ lưu một Contact.

#### Phạm vi

- Gắn nhiều Contact vào Opportunity.
- Role: decision maker, champion, influencer, evaluator, procurement, legal, finance, blocker, end user.
- Influence level, sentiment, engagement status, primary contact và notes.
- Stakeholder map dạng list/tree, không cần graph library ở MVP.
- Cảnh báo deal chưa có decision maker/champion.

#### Ngoài phạm vi

- Social network crawling.
- AI suy đoán sentiment tự động.

#### Dữ liệu dự kiến

- `opportunity_stakeholders`
- Enum/config stakeholder role, sentiment và influence.
- Timeline/audit khi đổi vai trò hoặc primary contact.

#### Authorization và test

- Contact và Opportunity đều phải nằm trong actor scope.
- Không cho liên kết Contact thuộc Company không phù hợp nếu chưa xác nhận.
- Không xóa Contact relation đang là stakeholder mà thiếu decision.
- Test scope, primary uniqueness, risk integration và Customer 360.

#### Nghiệm thu

- [ ] Opportunity có nhiều stakeholder với vai trò rõ ràng.
- [ ] Manager nhìn thấy deal thiếu decision maker/champion.
- [ ] Stakeholder map hiển thị tốt trên desktop/mobile.
- [ ] Người ngoài scope không đọc được stakeholder metadata.

---

### P11-03 — Contract & Renewal Management

#### Mục tiêu kinh doanh

Theo dõi hợp đồng sau khi thắng deal và tự chuẩn bị cơ hội gia hạn trước ngày hết hạn.

#### Phạm vi

- Contract từ Won Opportunity hoặc tạo có quyền.
- Số hợp đồng, giá trị, start/end date, billing cycle, owner, status và private attachments.
- Milestone: ký, hiệu lực, nghiệm thu, thanh toán, gia hạn.
- Reminder 90/60/30/7 ngày.
- Auto-create renewal Opportunity theo template và idempotency key.
- Renewal pipeline, owner, amount và relation với hợp đồng gốc.

#### Ngoài phạm vi

- Hóa đơn/kế toán.
- Ký số pháp lý.
- Revenue recognition phức tạp.

#### Dữ liệu dự kiến

- `contracts`
- `contract_milestones`
- `contract_renewal_runs`
- `contract_opportunity` hoặc relation trực tiếp phù hợp.

#### Rule và test

- End date phải sau start date.
- Một renewal cycle chỉ tạo một Opportunity.
- Scheduler retry không tạo trùng.
- Attachment private và authorize download.
- Contract scope kế thừa Company/owner/department nhưng phải có Policy riêng.

#### Nghiệm thu

- [ ] Won Opportunity tạo được Contract với snapshot đúng.
- [ ] Reminder được gửi đúng mốc và timezone.
- [ ] Renewal Opportunity tạo đúng một lần.
- [ ] Manager xem danh sách hợp đồng sắp hết hạn theo department.

---

### P11-04 — Manager Forecast Review

#### Mục tiêu kinh doanh

Manager chốt dự báo theo tuần/tháng với số liệu có giải trình, thay vì phụ thuộc bảng Excel và trao đổi rời rạc.

#### Phạm vi

- Forecast period theo tháng/quý.
- Sales submit commit/best case/pipeline với note.
- Manager review, adjust có lý do, approve hoặc return.
- Snapshot amount, probability, stage, expected close date và risk tại thời điểm submit.
- So sánh forecast với actual và đo forecast accuracy.
- Drill-down theo owner/department/pipeline/category.

#### Ngoài phạm vi

- AI forecast.
- Territory quota planning đầy đủ.

#### Dữ liệu dự kiến

- `forecast_periods`
- `forecast_submissions`
- `forecast_submission_items`
- `forecast_review_actions`

#### Rule và test

- Snapshot không đổi khi Opportunity thay đổi sau submit.
- Chỉ một active submission/user/period/version.
- Manager adjustment phải có reason và audit.
- Locked period không cho chỉnh.
- Mọi aggregate áp data scope.

#### Nghiệm thu

- [ ] Sales submit forecast theo kỳ.
- [ ] Manager review và điều chỉnh có lịch sử.
- [ ] Tổng số drill-down khớp summary.
- [ ] Xem được forecast accuracy của kỳ đã kết thúc.

---

### P11-05 — Email & Calendar Integration

#### Điều kiện bắt buộc

Hoàn tất security hardening, production callback URL và secret management trước khi kết nối provider thật.

#### Mục tiêu kinh doanh

Giảm nhập Activity thủ công bằng cách đồng bộ email/cuộc họp liên quan đến Lead, Contact, Company hoặc Opportunity.

#### Phạm vi MVP

- OAuth connect/disconnect cho một provider đầu tiên.
- Sync calendar event theo cursor/incremental token.
- Match người tham dự/email với Contact/Lead theo dữ liệu actor được phép xem.
- Ghi Activity dạng external snapshot; hỗ trợ link đến record.
- Manual link/unlink khi match không chắc chắn.
- Webhook/polling renewal và sync status UI.

#### Ngoài phạm vi

- Email marketing campaign.
- Đọc toàn bộ mailbox không giới hạn.
- Hai provider trong cùng feature đầu.
- Lưu attachment email ở MVP.

#### Dữ liệu dự kiến

- `external_connections`
- `external_sync_cursors`
- `external_events`
- `external_event_links`
- Encrypted provider tokens và scopes tối thiểu.

#### Bảo mật và rule

- Token mã hóa; log/audit không chứa token hoặc nội dung nhạy cảm.
- Chỉ sync folder/calendar được user cho phép.
- External event id + provider + account là idempotency key.
- Không auto-link record ngoài data scope.
- Disconnect phải revoke token khi provider hỗ trợ.

#### Test và nghiệm thu

- [ ] Connect/disconnect an toàn và hiển thị sync status.
- [ ] Event retry không tạo Activity trùng.
- [ ] Meeting có Contact phù hợp được link đúng.
- [ ] Match mơ hồ yêu cầu người dùng chọn.
- [ ] Token không xuất hiện trong log/audit/UI.

## 6. P12 — Platform, Integration và AI

### P12-01 — Workflow Automation Builder

#### Mục tiêu kinh doanh

Admin cấu hình automation phổ biến mà không yêu cầu lập trình cho mỗi thay đổi nhỏ.

#### Phạm vi MVP

- Trigger allowlist: record created/updated, status/stage changed, task overdue, quote accepted, contract expiring.
- Condition builder giới hạn field/operator theo entity.
- Action allowlist: create task, assign owner, add tag, send notification, update allowlisted field, enqueue webhook.
- Draft/published/paused/versioned workflow.
- Dry-run/test với record mẫu.
- Execution log, retry và dead-letter state.
- Loop guard, maximum depth và rate limit.

#### Ngoài phạm vi

- Script/code tùy ý.
- SQL tùy ý.
- Visual canvas kéo-thả phức tạp trong MVP.
- Chạy action không nằm trong allowlist.

#### Luồng

```text
Domain Event
→ lấy published workflow theo trigger
→ tạo execution với idempotency key
→ evaluate conditions trên scoped snapshot
→ enqueue từng action
→ thực thi với policy/system authority giới hạn
→ lưu result/error/duration
→ retry hoặc dead-letter
```

#### Dữ liệu dự kiến

- `automation_workflows`
- `automation_workflow_versions`
- `automation_triggers`
- `automation_conditions`
- `automation_actions`
- `automation_executions`
- `automation_action_executions`

#### Bảo mật và test

- Không cho workflow nâng quyền actor hoặc đọc record ngoài configured scope.
- Published version immutable; sửa phải tạo version mới.
- Loop/cycle detection.
- Idempotency, concurrency, retry/backoff và dead-letter.
- Mask secret trong action config và execution log.

#### Nghiệm thu

- [ ] Admin tạo workflow “Lead mới → tạo task follow-up”.
- [ ] Condition sai thì action không chạy và log giải thích được.
- [ ] Event retry không tạo task trùng.
- [ ] Workflow lỗi hiển thị execution/error và cho retry có kiểm soát.
- [ ] User thường không tạo/publish workflow.

---

### P12-02 — Webhook & API Integration Center

#### Mục tiêu kinh doanh

Cho phép SalesFlow trao đổi dữ liệu với website, ERP, kế toán, tổng đài và hệ thống nội bộ theo chuẩn có kiểm soát.

#### Phạm vi

- API `/api/v1` cho use case đã xác nhận; Form Request, Resource, policy và rate limit.
- Personal/integration credentials có scope.
- Outbound webhook subscription theo event allowlist.
- HMAC signature, timestamp, replay protection.
- Delivery log, retry exponential backoff, disable endpoint lỗi kéo dài.
- Inbound webhook adapter cho một integration mẫu.
- Admin UI xem connection, health và delivery.

#### Ngoài phạm vi

- Marketplace connector hoàn chỉnh.
- Cho user viết transformation code.
- Public API không version.

#### Dữ liệu dự kiến

- `integration_clients`
- `integration_credentials`
- `webhook_endpoints`
- `webhook_subscriptions`
- `webhook_deliveries`
- `inbound_webhook_receipts`

#### Bảo mật và test

- Secret hiển thị một lần, lưu hash/encrypted theo nhu cầu verify/sign.
- HMAC, timestamp tolerance, nonce/idempotency.
- SSRF protection cho outbound endpoint.
- Rate limit, payload minimization và data scope.
- Retry không đổi event ID; delivery có correlation ID.

#### Nghiệm thu

- [ ] Tạo endpoint và nhận webhook có signature hợp lệ.
- [ ] Delivery lỗi được retry theo backoff.
- [ ] Replay/tampered signature bị từ chối.
- [ ] Credential thiếu scope không gọi được endpoint.
- [ ] Admin xem được health nhưng không đọc lại secret.

---

### P12-03 — Customer Portal

#### Mục tiêu kinh doanh

Tạo không gian an toàn để khách hàng theo dõi báo giá, hợp đồng, tài liệu và trao đổi với Sales.

#### Phạm vi MVP

- Portal identity riêng hoặc magic link có expiry/step-up phù hợp.
- Company admin mời thành viên thuộc đúng Company.
- Xem quote, response, contract summary, shared file và timeline được công khai.
- Comment/request và notification hai chiều.
- Branding cơ bản.
- Portal policy tách khỏi internal CRM policy.

#### Ngoài phạm vi

- Helpdesk/ticketing đầy đủ.
- Payment portal.
- Cho khách xem audit/log nội bộ.

#### Dữ liệu dự kiến

- `portal_users`
- `portal_memberships`
- `portal_invitations`
- `portal_shared_resources`
- `portal_comments`

#### Bảo mật và test

- Tenant boundary theo Company tuyệt đối.
- Internal note/file mặc định không public.
- Download private file qua authorized/signed route.
- Invitation token hash, expiry, revoke và single-use.
- Rate limit login/magic link/comment.

#### Nghiệm thu

- [ ] Khách Company A không thể thấy dữ liệu Company B.
- [ ] Sales chọn rõ tài liệu nào được chia sẻ.
- [ ] Portal user xem/accept quote và xem hợp đồng của mình.
- [ ] Comment mới thông báo đúng owner.
- [ ] Internal audit/note không xuất hiện ở portal.

---

### P12-04 — Data Enrichment

#### Mục tiêu kinh doanh

Giảm nhập liệu Company/Contact và nâng chất lượng dữ liệu bằng nguồn thông tin bên ngoài có thể kiểm chứng.

#### Phạm vi MVP

- Provider abstraction nhưng chỉ triển khai một provider đầu tiên khi có API hợp lệ.
- Enrichment theo tax code hoặc domain.
- Preview diff; user chọn field áp dụng.
- Confidence/source/fetched_at cho từng đề xuất.
- Queue, cache, rate limit, retry và quota.
- Không ghi đè field người dùng đã xác nhận nếu chưa được đồng ý.

#### Ngoài phạm vi

- Scraping trái điều khoản.
- Tự động mua dữ liệu cá nhân.
- AI tự suy diễn dữ liệu không có nguồn.

#### Dữ liệu dự kiến

- `enrichment_requests`
- `enrichment_results`
- `enrichment_field_suggestions`
- `enrichment_provider_usages`

#### Bảo mật và test

- Chỉ gửi dữ liệu tối thiểu tới provider.
- Mask API key và PII trong log.
- Audit preview/apply/reject.
- Duplicate recheck sau khi áp tax code/email/phone.
- Test quota, retry, stale result và consent.

#### Nghiệm thu

- [ ] Nhập tax code/domain nhận được preview có nguồn.
- [ ] User chọn field áp dụng, không bị ghi đè ngoài ý muốn.
- [ ] Dữ liệu mới kích hoạt duplicate warning nếu cần.
- [ ] Provider lỗi không làm mất dữ liệu CRM.

---

### P12-05 — AI Meeting Summary & Next Best Action

#### Mục tiêu kinh doanh

Giảm thời gian ghi chú sau cuộc họp và giúp Sale nhận được đề xuất hành động tiếp theo có căn cứ, nhưng người dùng vẫn là người quyết định cuối.

#### Điều kiện bắt buộc

- Có policy AI, consent, retention, masking và provider contract được phê duyệt.
- Hoàn tất security hardening.
- Không đưa dữ liệu nhạy cảm sang provider ngoài khi chưa có quyền.

#### Phạm vi MVP

- Input từ meeting note/transcript do user chủ động cung cấp hoặc integration đã consent.
- Tạo draft summary: mục tiêu, vấn đề, nhu cầu, ngân sách, timeline, stakeholder, objection và action items.
- Gợi ý task, follow-up date, stage update hoặc risk factor.
- User review/edit/accept từng đề xuất.
- Lưu model/provider version, prompt version và provenance.
- Feedback hữu ích/không hữu ích để đánh giá chất lượng.

#### Ngoài phạm vi

- Tự động thay đổi stage hoặc gửi email không cần xác nhận.
- Chấm điểm cảm xúc hoặc đặc điểm cá nhân nhạy cảm.
- Training model trên dữ liệu khách hàng khi chưa có thỏa thuận.

#### Luồng

```text
User chọn nội dung
→ authorize record và consent
→ redact/mask
→ enqueue AI request
→ validate structured response
→ lưu draft suggestion
→ user review
→ accept từng action
→ service thực hiện action với authorization hiện tại
→ audit provenance và kết quả
```

#### Dữ liệu dự kiến

- `ai_requests`
- `ai_generated_summaries`
- `ai_action_suggestions`
- `ai_user_feedback`
- Không lưu raw prompt/response lâu hơn retention nếu không cần.

#### Bảo mật và test

- Prompt injection và untrusted content boundary.
- Output schema validation.
- Timeout/retry/cost/quota.
- PII masking và retention deletion.
- Không thực thi suggestion khi chưa authorize lại.
- Provider unavailable phải fallback sang nhập tay.

#### Nghiệm thu

- [ ] Summary là draft và có thể chỉnh trước khi lưu timeline.
- [ ] Task/stage/email suggestion không tự chạy.
- [ ] Accept task tạo đúng task một lần.
- [ ] Audit cho biết AI đã gợi ý gì và user đã chấp nhận gì.
- [ ] User không có quyền record không thể gửi dữ liệu sang AI.

## 7. Ma trận automation

| Trigger | Điều kiện ví dụ | Action | Feature sở hữu |
|---|---|---|---|
| Lead created/imported | Địa bàn + nguồn + giá trị | Assign owner, start SLA | P10-01 |
| Lead SLA approaching | Chưa có first touch | Reminder Sales | P10-01 |
| Lead SLA breached | Chưa có first touch | Reassign/escalate | P10-01 |
| Quote submitted | Discount/margin vượt ngưỡng | Create approval request | P10-02 |
| Opportunity entered stage | Đủ exit criteria | Create tasks/playbook | P10-03 |
| Opportunity stale | Không tương tác/stage aging | Risk score + notification | P10-04 |
| Quote accepted | Public response hợp lệ | Notify/update workflow | P10-05 |
| Contract expiring | Còn 90/60/30/7 ngày | Reminder/renewal Opportunity | P11-03 |
| Forecast submitted | Kỳ còn mở | Manager review notification | P11-04 |
| External meeting synced | Match Contact/Lead | Create/link Activity | P11-05 |
| Domain event | Workflow published + conditions match | Allowlisted actions | P12-01 |
| CRM event | Webhook subscription active | Signed delivery | P12-02 |
| Portal comment created | Resource shared | Notify owner | P12-03 |
| Enrichment requested | Quota/provider available | Fetch and preview diff | P12-04 |
| Meeting content submitted | Consent + scope valid | Draft summary/actions | P12-05 |

## 8. Quyền đề xuất

Các permission name cuối cùng phải đối chiếu catalog hiện tại trước khi migration/seed. Danh sách dự kiến:

- `lead-routing.view`, `lead-routing.manage`, `lead-routing.override`
- `quote-approval.view`, `quote-approval.approve`, `quote-approval.configure`
- `sales-playbook.view`, `sales-playbook.manage`
- `opportunity-risk.view`, `opportunity-risk.manage`
- `product.view`, `product.manage`, `price-book.manage`
- `contract.view`, `contract.manage`, `contract.renew`
- `forecast.submit`, `forecast.review`, `forecast.lock`
- `integration.view`, `integration.manage`
- `automation.view`, `automation.manage`, `automation.publish`
- `portal.manage`
- `enrichment.run`, `enrichment.configure`
- `ai-assistant.use`, `ai-assistant.configure`

`super-admin` có Gate bypass theo chuẩn hiện tại. `admin` không mặc định nhận mọi permission nếu catalog không cấp. Manager và Sales vẫn bị giới hạn bởi data scope.

## 9. Definition of Ready cho từng feature

Một feature chỉ bắt đầu code khi:

- Mục tiêu kinh doanh và owner nghiệp vụ đã rõ.
- Dependency đã hoàn tất hoặc có quyết định mock/feature flag rõ ràng.
- State machine, permission matrix và data scope được duyệt.
- Có quyết định về transaction, idempotency, queue, scheduler và realtime.
- Có mẫu dữ liệu hoặc UI flow đủ để nghiệm thu.
- Package/integration/provider cần thiết đã được đánh giá; chưa cài ở bước spec.
- Có checklist migration/backfill/rollback nếu chạm schema hiện có.

## 10. Definition of Done

- Đúng phạm vi và requirement.
- Backend authorization/data scope đầy đủ.
- Workflow concurrent/retry idempotent.
- Audit, notification và request/correlation ID đúng use case.
- UI có loading, empty, error, processing, confirmation, responsive, dark mode và keyboard state.
- Migration/seed/backfill an toàn và dùng `BIGINT`.
- Targeted test bao phủ success, denied, validation, duplicate/retry và edge case.
- Pint, PHPStan, frontend build và regression liên quan đạt.
- Checkpoint/docs/commit proposal được cập nhật.
- Chủ dự án hoàn thành kiểm thử thủ công trước khi chuyển feature.

## 11. Thứ tự triển khai khuyến nghị

### Đợt 1 — Demo và giá trị thương mại

1. P10-01 Smart Lead Auto-Routing.
2. P10-02 Quote Approval/PDF.
3. P10-03 Stage Playbook.
4. P10-04 At-Risk Deal.
5. P10-05 Public Quote.

### Đợt 2 — Vận hành B2B

6. P11-01 Product/Price Book.
7. P11-02 Buying Committee.
8. P11-03 Contract/Renewal.
9. P11-04 Forecast Review.
10. P11-05 Email/Calendar Integration.

### Đợt 3 — Platform và mở rộng

11. P12-01 Workflow Builder.
12. P12-02 Integration Center.
13. P12-03 Customer Portal.
14. P12-04 Data Enrichment.
15. P12-05 AI Sales Assistant.

Không nên bắt đầu từ AI hoặc Workflow Builder. Hai feature này chỉ tạo giá trị ổn định khi domain event, security, idempotency và các workflow cụ thể P10/P11 đã được chứng minh.
