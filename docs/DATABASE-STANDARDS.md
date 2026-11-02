Contains:

```text
Every business table:

tenant_id

Mandatory indexes

Soft deletes

Audit columns

UUID support ready

Created By
Updated By

No direct DB access from controller
```

Rules:

```sql
documents
transactions
invoices
contracts
audit_logs
usage_records
```

must always contain:

```sql
tenant_id
```
