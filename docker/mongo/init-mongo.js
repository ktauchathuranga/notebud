// Initialize database and TTL index on notes.createdAt (30 days)
db = db.getSiblingDB(process.env.MONGO_INITDB_DATABASE || "notes_app");

// Create notes collection if it doesn't exist
if (!db.getCollectionNames().includes("notes")) {
  db.createCollection("notes");
}

// Ensure TTL index exists: expire documents 30 days after createdAt
// 30 days = 2592000 seconds
db.notes.createIndex({ createdAt: 1 }, { expireAfterSeconds: 2592000 });

// Create users collection (optional)
if (!db.getCollectionNames().includes("users")) {
  db.createCollection("users");
}