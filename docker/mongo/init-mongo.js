// Initialize database and TTL index on notes.createdAt (30 days)
db = db.getSiblingDB(process.env.MONGO_INITDB_DATABASE || "notebud");

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

// Create chat collections
if (!db.getCollectionNames().includes("chat_requests")) {
  db.createCollection("chat_requests");
}

if (!db.getCollectionNames().includes("chats")) {
  db.createCollection("chats");
}

if (!db.getCollectionNames().includes("chat_messages")) {
  db.createCollection("chat_messages");
}

// Create indexes for chat collections
db.chat_requests.createIndex({ "to_user_id": 1, "status": 1 });
db.chat_requests.createIndex({ "from_user_id": 1, "to_user_id": 1 });
db.chats.createIndex({ "participants": 1 });
db.chats.createIndex({ "chat_id": 1 });
db.chat_messages.createIndex({ "chat_id": 1, "created_at": 1 });
db.users.createIndex({ "username": 1 });
db.users.createIndex({ "online": 1 });
