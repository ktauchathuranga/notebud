use anyhow::Result;
use jsonwebtoken::{Algorithm, DecodingKey, Validation, decode};
use serde::{Deserialize, Serialize};

#[derive(Debug, Serialize, Deserialize)]
pub struct Claims {
    pub user_id: String,
    #[serde(default)]
    pub username: String, // Make this optional with default
    #[serde(default)]
    pub session_id: String, // Make this optional with default
    #[serde(default)]
    pub permanent: bool, // Make this optional with default
    pub exp: usize,
    pub iat: usize,
}

pub struct JwtValidator {
    secret: String,
}

impl JwtValidator {
    pub fn new(secret: String) -> Self {
        Self { secret }
    }

    pub fn validate_token(&self, token: &str) -> Result<Claims> {
        // Create validation with more lenient settings
        let mut validation = Validation::new(Algorithm::HS256);

        // Allow some clock skew (5 minutes)
        validation.leeway = 300;

        // Don't require audience validation
        validation.validate_aud = false;

        // Log the secret length for debugging (but not the actual secret)
        log::debug!("JWT secret length: {}", self.secret.len());
        log::debug!("Token length: {}", token.len());

        // Validate token
        let token_data = decode::<Claims>(
            token,
            &DecodingKey::from_secret(self.secret.as_ref()),
            &validation,
        )
        .map_err(|e| {
            log::error!("JWT validation error: {}", e);
            log::debug!("Token preview: {}...", &token[..token.len().min(50)]);
            e
        })?;

        let claims = token_data.claims;

        // Log successful validation
        log::info!("JWT validated successfully for user_id: {}", claims.user_id);
        log::debug!(
            "Claims: username={}, session_id={}, permanent={}",
            claims.username,
            claims.session_id,
            claims.permanent
        );

        Ok(claims)
    }
}
