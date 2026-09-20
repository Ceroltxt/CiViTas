const { Client } = require('pg');
const client = new Client({
  connectionString: 'postgresql://postgres.ejsxdrvwvydtjxqywipg:kZMyKWXju7Iy1h9c@aws-1-us-east-1.pooler.supabase.com:6543/postgres'
});
client.connect().then(() => {
  return client.query('SELECT "ID_cargo", nome_cargo FROM cargo');
}).then(res => {
  console.log(res.rows);
  client.end();
});
