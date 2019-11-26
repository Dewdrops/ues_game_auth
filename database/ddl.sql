drop table if exists "users" cascade;
create table if not exists "users"
(
    id serial4 primary key,
    username text not null default '',
    email text not null default '',
    password text not null default '',
    wx_unionid text not null default '',
    email_verified boolean not null default false,
    data jsonb not null default '{}',
    created_at timestamptz not null default current_timestamp,
    updated_at timestamptz not null default current_timestamp
);
create unique index uk_wx_unionid on users (wx_unionid) where wx_unionid != '';
create unique index uk_email on users (email) where email != '';
create unique index uk_username on users (username) where username != '';

drop table if exists "weapp" cascade;
create table if not exists "weapp"
(
    id serial4 primary key,
    app_name text not null,
    wx_openid text not null,
    user_id int4 references users(id),
    created_at timestamptz not null default current_timestamp,
    updated_at timestamptz not null default current_timestamp
);
create unique index uk_wx_openid on weapp (app_name, wx_openid);
create unique index uk_user_id on weapp (app_name, user_id);


truncate users cascade ;

drop table if exists "game_pay" cascade;
create table if not exists "game_pay"
(
    id serial4 primary key,
    app_name text not null,
    user_id int4 references users(id),
    type text not null default 'GAME_PAY',
    amount float4 not null default 'nan'::float4,
    bill_no text not null default '',
    processed bool not null default false,
    extend_info jsonb not null default '{}',
    created_at timestamptz not null default current_timestamp,
    updated_at timestamptz not null default current_timestamp
);
create unique index uk_bill_no on game_pay (bill_no) where bill_no != '';

drop table if exists "tokens" cascade;
create table if not exists "tokens"
(
    id serial4 primary key,
    type text,
    token text,
    user_id int4 references users,
    expired_at int4,
    created_at timestamptz not null default current_timestamp,
    updated_at timestamptz not null default current_timestamp
);
create index idx_token on tokens (user_id, token);
