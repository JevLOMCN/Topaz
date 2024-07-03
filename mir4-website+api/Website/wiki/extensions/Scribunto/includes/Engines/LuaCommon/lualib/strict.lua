-- strict.lua (fork of http://www.lua.org/extras/5.1/strict.lua)
-- checks uses of undeclared global variables
-- All global variables must be 'declared' through a regular assignment
-- (even assigning nil will do) in a main chunk before being used
-- anywhere or assigned to inside a function.
-- distributed under the Lua license: http://www.lua.org/license.html
-- Scribunto modifications:
-- * exempt arg, used by require()
-- * remove what(), since debug.getinfo isn't allowed

local error, rawset, rawget = error, rawset, rawget

local mt = getmetatable(_G)
if mt == nil then
  mt = {}
  setmetatable(_G, mt)
end

mt.__newindex = function (t, n, v)
  if n ~= "arg" then
    error("assign to undeclared variable '"..n.."'", 2)
  end
  rawset(t, n, v)
end

mt.__index = function (t, n)
  if n ~= "arg" then
    error("variable '"..n.."' is not declared", 2)
  end
  return rawget(t, n)
end
