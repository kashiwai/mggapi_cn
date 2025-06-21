# MGGO API Provider for China

Simple PHP API for MGGO China iframe authentication.

## Endpoints

- `GET /api/` - API information
- `POST /api/verify-token` - Token verification
- `POST /api/launch/token/generate` - Generate game token

## Deployment on Render.com

1. Push this code to GitHub
2. Connect to Render.com
3. Deploy as Web Service

## Test

```bash
# API Info
curl https://your-app.onrender.com/api/

# Verify Token
curl -X POST https://your-app.onrender.com/api/verify-token \
  -H "Content-Type: application/json" \
  -d '{"token":"mggo_VP_TEST_001_123456"}'

# Generate Token
curl -X POST https://your-app.onrender.com/api/launch/token/generate \
  -H "Content-Type: application/json" \
  -d '{"operator":"VP_TEST","user_id":"test_001","username":"TestUser"}'
```