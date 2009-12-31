DROP TABLE subscriptions;
CREATE TABLE subscriptions(
  sid      integer not null,
  uid      integer not null,
  stype    text not null
);

DROP TABLE subscriptions_holding;
CREATE TABLE subscriptions_holding (
  rid serial,
  content text not null,
  ptype text not null,
  op text not null,
  pid integer not null default 0
);
COMMENT ON COLUMN subscriptions_holding.rid IS 'Unique row ID';
COMMENT ON COLUMN subscriptions_holding.content IS 'The node array';
COMMENT ON COLUMN subscriptions_holding.ptype IS 'post type - node or comment';
COMMENT ON COLUMN subscriptions_holding.op IS 'The operation on the node';
COMMENT ON COLUMN subscriptions_holding.puid IS 'The ID of the poster';
