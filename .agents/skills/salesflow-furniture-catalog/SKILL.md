---
name: salesflow-furniture-catalog
description: Build, review, or extend SalesFlow CRM's B2B office furniture catalog. Use for furniture Category, Product, Variant, Supplier offers, dimensions, materials, finishes, pricing, warranty, lead time, Opportunity/Quote product selection, BOQ preparation, and P11-F01 through P11-F05.
---

# SalesFlow Furniture Catalog

Read `docs/FURNITURE_CATALOG_REQUIREMENTS.md`, `docs/requirements.md` and `docs/FURNITURE_CATALOG_PHASES.md` before changing this vertical.

## Rules

- Keep the technical-layer tree: Model → Repository → Service → Livewire.
- Model Product as the shared design/model and ProductVariant as the sellable SKU.
- Store dimensions in millimetres and money as decimal. Keep material, color and finish as snapshot-friendly text.
- Model Supplier offers separately; enforce one preferred Supplier per Variant in the Service transaction.
- Model product images in `product_media`; store files on the configured filesystem disk, keep only metadata in PostgreSQL, and enforce one primary image per Product.
- Product media may reference a Variant. Protect CRM image reads with ProductPolicy and never expose an internal MinIO hostname directly to the browser.
- Apply ProductPolicy/Data Scope before product/variant reads and mutations. Use Product permissions for aggregate child operations.
- Treat commercial status as availability for Sales only, never as inventory.
- Preserve Opportunity/Quote snapshots; catalog changes must not mutate issued documents.
- Use full-page Product editor/detail; use Flux modal for short Variant/Supplier actions.
- Add audit, targeted tests, route/sidebar scan and update the furniture checkpoint.
