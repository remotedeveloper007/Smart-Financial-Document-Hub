# Integration Settings (Per-Team)

Purpose
- Allow teams to store provider-specific connection details (API URL, tokens, security codes, webhooks) through the Integrations UI.

Fields saved to `credentials` (JSON) on the `integrations` table:
- `access_token`: provider access token
- `url`: optional API base URL for self-hosted or custom endpoints
- `security_code`: optional secret or code required by some providers
- `webhook`: optional webhook URL to receive events

Notes
- Credentials are stored as an array (cast to AsArrayObject) on the `Integration` model.
- For OAuth providers tokens are still managed by the respective OAuth services and saved on callback flows.
- These fields are editable via the Integrations page: [resources/js/Pages/Integrations/Index.tsx](resources/js/Pages/Integrations/Index.tsx#L1-L200)

Testing
- `tests/Feature/IntegrationFeatureTest.php` verifies persistence of `credentials` including `url`, `security_code`, and `webhook`.

Migration/Model
- `database/migrations/*create_integrations_table.php` contains the `credentials` JSON column.
- `app/Models/Integration.php` casts `credentials` to `AsArrayObject`.
