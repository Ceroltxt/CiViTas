const { Client } = require('pg');
const client = new Client({
  connectionString: 'postgresql://postgres.ejsxdrvwvydtjxqywipg:kZMyKWXju7Iy1h9c@aws-1-us-east-1.pooler.supabase.com:6543/postgres'
});
client.connect().then(() => {
  return client.query("UPDATE equipe SET workspace_id = 2 WHERE workspace_id IS NULL");
}).then(res => {
  console.log('Fixed', res.rowCount, 'equipes');
  client.end();
});
