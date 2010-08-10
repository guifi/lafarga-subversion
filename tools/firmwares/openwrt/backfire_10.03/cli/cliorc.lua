
json = require("json")

function printTable(ptable,pn)
  local x=pn
  for k,v in pairs(ptable) do
    if (type(v)=="table") then
      io.write(string.format("%s\n",string.rep("      ",x)..k.." {"))
      printTable(v,x+1)
    else
      io.write(string.format("%s\n",string.rep("      ",x)..k.."="..v))
    end 
  end
end

function loadDef(ptDefDir)
  local defSection=""
  local defSubSection=""
  local defItem=""
  local defText=""
  for line in io.lines("config.txt") do 
    --print(line)
    local n=string.find(line,"%S")
    if (n~=nil) then
      line=string.sub(line,n)
    end
    if(string.find(line,"/sdefine")~=nil) then
      if(string.find(line,"%[/sdefine")~=nil) then
      	if (defSection~="" and defSubSection~="") then
      	  if (endSection(defSubSection,line)) then
            if(defSection=="dirs") then  
              if (loadDir(defSubSection,defItem,defText)==false) then
                return false
              end
            end
            if(defSection=="commands") then  
              if (loadCommand(defSubSection,defItem,defText)==false) then
                return false
              end
            end      	  
     	      defSubSection=""
     	      defItem=""
     	      defText=""
      	  end
		    else
		      print("Error not init define or sdefine: "..line)
		      return false
		    end
      end 
    elseif(string.find(line,"sdefine")~=nil) then
      if(string.find(line,"%[sdefine")~=nil) then
        if(defSection~="" and defSubSection=="") then
          local ret,s=parseDefine(line)
        	if(ret) then
            defSubSection=s
          else
            print("Error label sdefine: "..line)
            return false
          end
        else
          print("Error init sdefine: "..line)
          return false
        end
      end
    elseif(string.find(line,"/define")~=nil) then
      if(string.find(line,"%[/define")~=nil) then
      	if defSection~="" then
      	  if (endSection(defSection,line)) then
      	    if(defSubSection=="") then
      	      defSection=""
      	    else
		          print("Error not end sdefine: "..line)
		          return false
      	    end
      	  end
		    else
		      print("Error not init define: "..line)
		      return false
		    end
      end 
    elseif(string.find(line,"define")~=nil) then
      if(string.find(line,"%[define")~=nil) then
        if(defSection=="") then
          local ret,s=parseDefine(line)
        	if(ret) then
            defSection=s
          else
            print(s)
            return false
          end
        else
          print("Error not end define: "..defSection)
          return false
        end
      end
    elseif(defSection~="" and defSubSection~="") then
      --print(defSection.." "..defSubSection.." "..line) 
      local ret
      local item
      local s
      ret,item,s=parseItem(line)
      if (ret) then
        if (item~="") then
          if (defItem~="") then
            if(defSection=="dirs") then  
              if (loadDir(defSubSection,defItem,defText)==false) then
                return false
              end
            end
            if(defSection=="commands") then  
              if (loadCommand(defSubSection,defItem,defText)==false) then
                return false
              end
            end
            defItem=item
            defText=s
          else
            defItem=item
            defText=s
          end
        else
          if (defItem~="") then
            defText=defText.." "..s
          else
            print("Error Item not initiate: "..line)
            return false
          end
        end
      else
        print(item)
        return false
      end
    end 
  end
  return true
end

function loadDir(pdefSubSection,pdefItem,pdefText)
  if(tDefDir[pdefSubSection]==nil) then
    tDefDir[pdefSubSection]={}
    tDefDir[pdefSubSection]["propertys"]={}
    tDefDir[pdefSubSection]["subdirs"]={}
    tDefDir[pdefSubSection]["commands"]={}
  end
  table.insert(tDefDir[pdefSubSection]["subdirs"],pdefItem)

  if(tDefDir[pdefItem]==nil) then
    tDefDir[pdefItem]={}
    --tDefDir[pdefSubSection]["propertys"]={}
    tDefDir[pdefItem]["subdirs"]={}
    tDefDir[pdefItem]["commands"]={}
  end
  tDefDir[pdefItem]["propertys"]=json.decode(pdefText)
  return true
end

function loadCommand(pdefSubSection,pdefItem,pdefText)
  if(tDefDir[pdefSubSection]~=nil) then
    tDefDir[pdefSubSection]["commands"][pdefItem]=json.decode(pdefText)
  end
  return true
end

function parseItem(pline)
  local v
  local sw
  local n
  if(string.find(string.sub(pline,1,1),"[_%w]")~=nil) then
    local n=string.find(pline,"=")
    if(n~=nil) then
      v=string.sub(pline,1,n-1)
      sw=0
      for w in string.gmatch(v,"%W") do
        sw=1
        break
      end
      if (sw==1) then
        return false,"Error item: "..pline,""
      else
        return true,v,string.sub(pline,n+1)
      end
    else
      return false,"Error item: "..pline,""
    end
  else
    return true,"",pline
  end
end

function endSection(pdefSection,psLine)
	local ret,s=parseDefine(psLine)
  if(ret) then
    if(pdefSection==s) then
    else
      print("Error end section: "..line)
      return false
    end  
  else
    print(s)
    return false
  end
  return true
end

function parseDefine(psLine)
  local v
  n=0	
  for w in string.gmatch(psLine,"%l+") do
    v=w
    n=n+1
    if(n==2) then
      break
    end	
  end
  if(n==2) then
    return true,v
  else
    return false,"Error define or sdefine: "..psLine
  end
end

function parse(pInput)
  local tCommand={}
  for w in string.gmatch(pInput, "%S+") do
    table.insert(tCommand,w)
  end
  fexec(tCommand)
  return true
end

function fexec(ptCommand)
  if(ptCommand[1]=="cd") then
    exec_cd(ptCommand)
  elseif(ptCommand[1]=="?") then
    exec_list(ptCommand)
  elseif(ptCommand[1]=="ls") then
    exec_print(ptCommand)
  elseif(ptCommand[1]=="help") then
    exec_help(ptCommand)
  else
    exec_command(ptCommand)
  end
end

function exec_help(ptCommand)
  print "   cd = change directory"
  print "   ?  = list directorys and commands"
  print "   ls = list directory table contents"
  print "   ls all = list all table contents"
  print "   help = list this"
  print "   q  = exit"
end

function exec_command(ptCommand)
  local vc=""
  local d
  local n=table.maxn(tDir)
  if (n==0) then
    d="root"
  else
    d=tDir[n]
  end
  if (tDefDir[d].commands[ptCommand[1]]~=nil) then
    vc=ptCommand[1]
  end
  if (vc~="") then
    if(tDefDir[d].commands[vc].exec~=nil) then
      n=table.maxn(tDefDir[d].commands[vc].exec)
      if (n==0) then
        print("exec command "..vc.." not defined")
      else
        for w=1,n do
          print("execute command "..tDefDir[d].commands[vc].exec[w])
        end
      end
    else
      print("exec "..vc.." not defined")
    end
  else
    print(ptCommand[1].." No such command")
  end
end

function exec_print(ptCommand)
  if (ptCommand[2]=="all") then
    printTable(tDefDir,0)
  else
    local d
    local n=table.maxn(tDir)
    if (n==0) then
      d="root"
    else
      d=tDir[n]
    end
    printTable(tDefDir[d],0)
  end
end

function exec_list(ptCommand)
    local d
    local v=""
    local n=table.maxn(tDir)
    if (n==0) then
      d="root"
    else
      d=tDir[n]
    end
    n=table.maxn(tDefDir[d].subdirs)
    for w=1,n do
      v=v.."["..tDefDir[d].subdirs[w].."] "
    end
    for k,w in pairs(tDefDir[d].commands) do
      v=v..k.." "
    end
    print(v)
end

function exec_cd(ptCommand)
  if(ptCommand[2]=="..") then
    if(table.maxn(tDir)>0) then
      table.remove(tDir)
    end
  else
    local sw=0
    local v
    local n=table.maxn(tDir)
    if (n==0) then
      v="root"
    else
      v=tDir[n]
    end
    n=table.maxn(tDefDir[v].subdirs)
    for w=1,n do
      if (tDefDir[v].subdirs[w]==ptCommand[2]) then
        table.insert(tDir,ptCommand[2])
        sw=1
        break
      end
    end
    if (sw==0) then
      print(ptCommand[2].." No such directory")
    end
  end
end

tDefDir={}
tDir={}
vInput=""

if (loadDef(tDefDir)) then
  repeat
    local d=""
    local n=table.maxn(tDir)
    if(n==0) then
      d="/"
    else
      for i=1,n do
        d=d.."/"..tDir[i]
      end
    end
    io.write(d..">")
    vInput=io.read("*line")
    if (vInput=="q") then
    else
      n=parse(vInput)
    end
  until (vInput=="q")
end



