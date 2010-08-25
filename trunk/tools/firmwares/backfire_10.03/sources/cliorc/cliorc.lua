
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

function arraytostring(parray)
  local v=""
  if parray~=nil then
    if parray[1]==nil then --if is key-value print key
      for k,w in pairs(parray) do
        if v=="" then
          v=v..k
        else
          v=v..", "..k
        end
      end
    else                   --if is array
      for k,w in ipairs(parray) do
        if v=="" then
          v=v..w
        else
          v=v..", "..w
        end
      end
    end
  end
  return v
end

function fconcat(parray)
  local v=""
  if parray~=nil then
    for k,w in ipairs(parray) do
      if w~=nil and w~="" then
        v=v..w
      else
        v=""
        break
      end
    end
  end
  return v
end


function printerror(ptstring)
  for k,w in ipairs(ptstring) do
    if w~=nil and w~="" then
      io.write(string.format("%s\n",w))
    end
  end
end

function loadDefs(ptDefDir)
  local b
  local e
  local s
  local r,verr
  io.write("load config")
  r,verr = loadDef(ptDefDir,"","config")
  if r then
    io.write(string.format("%s\n","       ok"))
    for line in io.lines("config_plugins") do
      b,e = string.find(line,"%w+")
      if (b~=nil) then
        s=string.sub(line,b,e)
        io.write("load config plugin "..s)
        r,verr = loadDef(ptDefDir,"plugins/"..s.."/","config")
        if r then
          io.write(string.format("%s\n","       ok"))
        else
          io.write(string.format("%s\n","       failed"))
          io.write(string.format("%s\n",verr))
        end
      end
    end  
    return true
  else
    io.write(string.format("%s\n","       failed"))
    io.write(string.format("%s\n",verr))
    return false
  end
end

function loadDef(ptDefDir,ppath,pfile)
  local defSection=""
  local defSubSection=""
  local defItem=""
  local defText=""
  local swInit=0
  local n=0
  local e=0
  for line in io.lines(ppath..pfile) do 
    --print(line)
    n=string.find(line,"%S")
    if (n~=nil) then
      line=string.sub(line,n)
    end
    if swInit < 4 and string.sub(line,1,16)=="//cliorc version" then
      n,e=string.find(string.sub(line,17),"%S")
      v=string.sub(line,16+n,16+e)
      if tonumber(version) < tonumber(v) then
        return false,"Error: config file on the "..ppath.." not work for this version of cliorc"
      end
      swInit=swInit+1
    elseif swInit < 4 and string.sub(line,1,16)=="//config version" then
      n,e=string.find(string.sub(line,17),"%S+")
      io.write(string.format("%s"," version "..string.sub(line,16+n,16+e)))
      swInit=swInit+2
    elseif string.sub(line,1,2)~="//" and line~="" then
      if swInit==3 then
        swInit=100
      elseif swInit~=100 then
        return false,"Error: config file on the "..ppath.." not versions defined"
      end
      if(string.find(line,"/sdefine")~=nil) then
        if(string.find(line,"%[/sdefine")~=nil) then
        	if (defSection~="" and defSubSection~="") then
        	  if (endSection(defSubSection,line)) then
        	    if defItem~="" then
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
                if(defSection=="formats") then  
                  if (loadFormat(defSubSection,defItem,defText)==false) then
                    return false
                  end
                end      	  
              end
       	      defSubSection=""
       	      defItem=""
       	      defText=""
        	  end
		      else
		        return false,"Error not init define or sdefine: "..line
		      end
        end 
      elseif(string.find(line,"sdefine")~=nil) then
        if(string.find(line,"%[sdefine")~=nil) then
          if(defSection~="" and defSubSection=="") then
            local ret,s=parseDefine(line)
          	if(ret) then
              defSubSection=s
            else
              return false,"Error label sdefine: "..line
            end
          else
            return false,"Error init sdefine: "..line
          end
        end
      elseif(string.find(line,"/define")~=nil) then
        if(string.find(line,"%[/define")~=nil) then
        	if defSection~="" then
        	  if (endSection(defSection,line)) then
        	    if(defSubSection=="") then
        	      defSection=""
        	    else
		            return false,"Error not end sdefine: "..line
        	    end
        	  end
		      else
		        return false,"Error not init define: "..line
		      end
        end 
      elseif(string.find(line,"define")~=nil) then
        if(string.find(line,"%[define")~=nil) then
          if(defSection=="") then
            local ret,s=parseDefine(line)
          	if(ret) then
              defSection=s
            else
              return false,s
            end
          else
            return false,"Error not end define: "..defSection
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
              if(defSection=="formats") then  
                if (loadFormat(defSubSection,defItem,defText)==false) then
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
              return false,"Error Item not initiate: "..line
            end
          end
        else
          return false, item
        end
      end
    end 
  end
  return true
end

function loadDir(pdefSubSection,pdefItem,pdefText)
  if(tDefDir[pdefSubSection]==nil) then
    tDefDir[pdefSubSection]={}
    tDefDir[pdefSubSection]["properties"]={}
    tDefDir[pdefSubSection]["subdirs"]={}
    tDefDir[pdefSubSection]["commands"]={}
    tDefDir[pdefSubSection]["formats"]={}
  end
  if(tDefDir[pdefSubSection].subdirs[pdefItem]==nil) then
    table.insert(tDefDir[pdefSubSection]["subdirs"],pdefItem)
  end
  if(tDefDir[pdefItem]==nil) then
    tDefDir[pdefItem]={}
    tDefDir[pdefItem]["subdirs"]={}
    tDefDir[pdefItem]["commands"]={}
    tDefDir[pdefItem]["properties"]=json.decode(pdefText)
    tDefDir[pdefItem]["formats"]={}
  end
  return true
end

function loadCommand(pdefSubSection,pdefItem,pdefText)
  if(tDefDir[pdefSubSection]~=nil and tDefDir[pdefSubSection].commands[pdefItem]==nil) then
    tDefDir[pdefSubSection].commands[pdefItem]=json.decode(pdefText)
  end
  return true
end

function loadFormat(pdefSubSection,pdefItem,pdefText)
  if(tDefDir[pdefSubSection]~=nil and tDefDir[pdefSubSection].formats[pdefItem]==nil) then
    tDefDir[pdefSubSection].formats[pdefItem]=json.decode(pdefText)
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
        if(w~="_") then
          sw=1
          break
        end
      end
      if (sw==1) then
        return false,"Error: forbidden character in the command on the item: "..pline,""
      else
        return true,v,string.sub(pline,n+1)
      end
    else
      return false,"Error: = not found on the item: "..pline,""
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
      print("Error end section: "..psLine)
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
  local n=table.maxn(ptCommand)
  if ptCommand[1]==nil then
  elseif(ptCommand[1]=="cd") then
    exec_cd(ptCommand)
  elseif(ptCommand[1]=="?") then
    exec_list(ptCommand)
  elseif(ptCommand[1]=="ls") then
    exec_print(ptCommand)
  elseif(ptCommand[1]=="help") then
    exec_help(ptCommand)
  elseif(ptCommand[n]=="?") then
    exec_directHelp(ptCommand)
  elseif(string.sub(ptCommand[1],1,1)==".") then
    exec_directCommand(ptCommand)
  else
    exec_command(diractual(),ptCommand)
  end
end

function exec_help(ptCommand)
  print "internals commands"
  print "   cd        = change directory            : cd interface; cd interface/bonding; cd /interface; cd .."
  print "   cd .dir   = change directory search path: cd .ethernet"
  print "   ?         = list directory and commands : ?; ? interface"
  print "   ? -l      = equal ? witch description   : ? -l; ? -l interface"  
  print "   command ? = help command                : .tool ?; .tool ping ?; ping ?"
  print "   ls        = list table contents"
  print "   ls all    = list all table contents"
  print "   help      = list this"
  print "   quit      = exit"
  print ""
  print "externals commands"
  print "   execute command in dir  = command ...          : /tool>ping 192.168.1.1"
  print "   execute direct command  = .subdir command ...  : />.tool ping 192.168.1.1"
  print "   execute command in root = . command ...."
  print ""
  print "also config"
  print "   exec. direct command shell = . command ...     : . ip -f inet link"
end

function exec_directCommand(ptCommand)
  local v=string.sub(ptCommand[1],2)
  if v=="" then
    v="root"
  end
  if(isdir("",v)) then
    table.remove(ptCommand,1)
    exec_command(v,ptCommand)
  else
    print(v.." no such directory")
  end
end

function exec_command(pdir,ptCommand)
  local vc=""
  local d=pdir
  local tvars={}
  local sws=0
  if tDefDir[d].properties.mode~=nil then
    if string.find(tDefDir[d].properties.mode,"d")~=nil then
      if ptCommand[1]==d then
        table.remove(ptCommand,1)
      end
    end
    if string.find(tDefDir[d].properties.mode,"s")~=nil then
      sws=1
    end
  end
  if (tDefDir[d].commands[ptCommand[1]]~=nil) then
    vc=ptCommand[1]
  elseif sws==1 and tDefDir[d].commands[ptCommand[2]]~=nil then
    vc=ptCommand[2]
  elseif tDefDir[d].commands.default~=nil then
    vc="default"
  end
  if (vc~="") then
    if(tDefDir[d].commands[vc].exec~=nil) then
      n=table.maxn(tDefDir[d].commands[vc].exec)
      if (n==0) then
        print("exec command "..vc.." not defined")
      else
        if (parseVars(tDefDir[d].commands[vc],ptCommand,tvars)) then
          --printTable(tvars,1)
          if verifyVars(d,vc,tvars) then
            local vexec=""
            for w=1,n do
              vexec=commandVars(tvars,tDefDir[d].commands[vc].exec[w])
              if (vexec~=nil) then
                print("execute command: "..vexec)
                os.execute(vexec)
              else
                print("Error subs vars in exec on the exec: "..tvars,tDefDir[d].commands[vc].exec[w])
              end
            end
          end
        end
      end
    else
      print("exec "..vc.." not defined")
    end
  else
    print(ptCommand[1].." No such command")
  end
end

function verifyVars(pd,pvc,ptvars)
  local vars=tDefDir[pd].commands[pvc].vars
  local ret
  local val
  if vars~=nil then
    for k,v in pairs(ptvars) do
      if tDefDir[pd].commands[pvc].vars[k]~=nil then
        if type(v)=="table" then  --var type $property=$value
          if verifyVarsVars(pd,pvc,ptvars,k,v.key) then --verify $property
            if vars[v.valuevar]~=nil and vars[v.valuevar].format~=nil then
              if vars[v.valuevar].format=="$"..k then --verify value for option  
                ret,val=verifyVarsVars(pd,pvc,ptvars,v.key,v.value)
                if ret then
                  if val~=nil then  --change value
                    v.value=val
                  end
                else
                  return false
                end
              else      --verify $value
                if not verifyVarsVars(pd,pvc,ptvars,v.valuevar,v.value) then
                  return false
                end
              end
            end
          else
            return false
          end
        else  --var type $var
          if not verifyVarsVars(pd,pvc,ptvars,k,v) then
            return false
          end
        end
      end
    end
  end
  return true
end

function verifyVarsVars(pd,pvc,ptvars,k,v)
  local t
  local n=0
  local vars
  local vk
  local defcommand=tDefDir[pd].commands[pvc]
  local vars=defcommand.vars
  if vars[k]~=nil and vars[k].format~=nil then
    vformat=nil
    if string.sub(vars[k].format,1,1)=="$" then
      vk=string.sub(vars[k].format,2)
      if tDefDir[pd].formats[vk]~=nil and tDefDir[pd].formats[vk].format~=nil then
        vformat=tDefDir[pd].formats[vk]
      elseif tDefDir.root.formats[vk]~=nil and tDefDir.root.formats[vk].format~=nil then
        vformat=tDefDir.root.formats[vk]
      end
    else
      vformat=vars[k]
    end 
    if vformat~=nil then
      t={string.find(v,vformat.format)}
      n=table.maxn(t)
      if t[1]==1 and t[2]==string.len(v) then
        if vformat.values~=nil and vformat.values[1]~=nil then
          for i=3,n do
            if vformat.values[i-2]~=nil then
              if vformat.values[i-2][1]=="~" then
                if tonumber(t[i])>=vformat.values[i-2][2] and tonumber(t[i])<=vformat.values[i-2][3] then
                else
                  printerror({"Error "..v.." invalid value",defcommand.command,fconcat({"Options: ",arraytostring(vformat.options)}),fconcat({k," = ",trim(vformat.help)})})
                  return false
                end
              elseif vformat.values[i-2][1]=="l" then
                local swok=0
                for x,w in ipairs(vformat.values[i-2]) do
                  if x>1 then
                    if t[i]==w then
                      swok=1
                      break
                    end
                  end
                end
                if swok==0 then
                  printerror({"Error "..v.." invalid value",defcommand.command,fconcat({"Options: ",arraytostring(vformat.options)}),fconcat({k," = ",trim(vformat.help)})})
                  return false
                end
              end
            end  
          end
        end
        if vformat.options~=nil and vformat.options[1]~=nil then   --search options array
          local swok=0
          for x,w in ipairs(vformat.options) do
            if v==w then
              swok=1
              break
            end
          end
          if swok==0 then
            printerror({"Error "..v.." invalid value",defcommand.command,fconcat({"Options: ",arraytostring(vformat.options)}),fconcat({k," = ",trim(vformat.help)})})
            return false
          end
        elseif vformat.options~=nil and table.maxn(vformat.options)>0 then                        --search options key-value
          local vval
          for x,w in pairs(vformat.options) do
            if v==x then
              vval=w
              break
            end
          end
          if vval==nil then
            printerror({"Error "..v.." invalid value",defcommand.command,fconcat({"Options: ",arraytostring(vformat.options)}),fconcat({k," = ",trim(vformat.help)})})
            return false
          else
            return true,vval   --return new value for key
          end
        end              
      else
        printerror({"Error "..v.." invalid format",defcommand.command,fconcat({"Options: ",arraytostring(vformat.options)}),fconcat({k," = ",trim(vformat.help)})})
        return false
      end
    end
  end
  return true
end

function commandVars(ptvars,pcommand)
  local vexec=pcommand
  for k,v in pairs(ptvars) do
    if type(v)=="table" then
      vexec=string.gsub(vexec,k,v.key)
      vexec=string.gsub(vexec,v.valuevar,v.value)
    else
      vexec=string.gsub(vexec,k,v)
    end
  end
  return vexec
end

function parseVars(ptdefCommand,ptCommand,ptvars)
  local vcd=ptdefCommand.command
  local t={}
  local n=0
  local x=0
  local e=0
  local nmax=table.maxn(ptCommand)
  local var
  local var1
  local symbol
  local nw=0
  for w in string.gmatch(vcd, "%S+") do
    table.insert(t,w)  --t table of words in defCommand
    n=n+1
  end  
  for i=1,n do
    nw=nw+1
    if nw>nmax then
      ptvars[t[i]]=""
    elseif string.find(t[i],"=")~=nil then
      x=string.find(t[i],"=")
      if string.sub(t[i],1,1)=="$" then
        var=string.sub(t[i],1,x-1)
        if string.sub(t[i],x+1,x+1)=="$" then
          var1=string.sub(t[i],x+1)
          e=string.find(ptCommand[nw],"=")
          if e~=nil then
             ptvars[var]={key=string.sub(ptCommand[nw],1,e-1);valuevar=var1;value=string.sub(ptCommand[nw],e+1)}
             --ptvars[var1]=string.sub(ptCommand[nw],e+1)
          else
            print ("Error '=' not found in '"..ptCommand[nw].."'")
            return false
          end
        else
          print ("Error value $ not found in define command '"..t[i].."' on the command: "..vcd)
          return false
        end
      else 
        print ("Error init $ not found in define command '"..t[i].."' on the command: "..vcd)
        return false
      end
    elseif t[i]=="$_restcommand" then
      ptvars[t[i]]=""
      if ptCommand[nw]=="default" then
        nw=nw+1
      end
      for j=nw,nmax do
        ptvars[t[i]]=ptvars[t[i]].." "..ptCommand[j]
      end
      break
    elseif string.sub(t[i],1,1)=="$" then
      ptvars[t[i]]=ptCommand[nw]
    elseif string.sub(t[i],1,1)=="[" then
      x=string.find(t[i],"%$")
      e=string.find(t[i],"%]")
      if e~=nil and x~=nil then
        var=string.sub(t[i],x,e-1)
        symbol=string.sub(t[i],2,x-1)
        if string.sub(ptCommand[nw],1,string.len(symbol))==symbol then
          ptvars[var]=ptCommand[nw]
        else
          ptvars[var]=""
          nw=nw-1  
        end 
      else
        print ("Error ] or $ not found in define command '"..t[i].."' on the command: "..vcd)
        return false
      end
    else
      if t[i]~=ptCommand[nw] then
        print ("Error "..ptCommand[nw].." not found correctly in define command '"..t[i].."' on the command: "..vcd)
        return false
      end 
    end
  end 
  return true
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
    local sw=0
    local c=""
    if (ptCommand[2]==nil) then
      d=diractual()
    elseif(ptCommand[2]=="-l") then
      sw=1
      if(ptCommand[3]==nil) then
        d=diractual()
      else
        d=ptCommand[3]
        if(isdir("",d)) then
        elseif(iscommand("",d)) then
          c=d
          d=diractual()
        else
          print(d.." no such directory")
          return 1
        end
      end
    else
      d=ptCommand[2]
      if(isdir("",d)) then
      elseif(iscommand("",d)) then
        c=d
        d=diractual()
      else
        print(d.." no such directory")
        return 1
      end
    end
    n=table.maxn(tDefDir[d].subdirs)
    if(sw==1 and c=="") then
      for w=1,n do
        v=v..string.format("%s\n","["..tDefDir[d].subdirs[w].."]     "..trim(tDefDir[tDefDir[d].subdirs[w]].properties.desc))
      end
      for k,w in pairs(tDefDir[d].commands) do
        v=v..string.format("%s\n",k.."     "..trim(tDefDir[d].commands[k].desc))
      end
    elseif c~="" then
      v=string.format("%s\n",tDefDir[d].commands[c].command)
    else
      for w=1,n do
        v=v.."["..tDefDir[d].subdirs[w].."] "
      end
      for k,w in pairs(tDefDir[d].commands) do
        v=v..k.." "
      end
    end
    if c=="" then
      print("directory: "..d)
    end
    print(v)
end

function exec_directHelp(ptCommand)
    local d=""
    local v=""
    local n=1

  if(string.sub(ptCommand[1],1,1)==".") then
    v=string.sub(ptCommand[1],2)
    if(isdir("",v)) then
      d=v
    else
      print ("Error "..v.." not is directory")
      return false
    end
    n=2
  else
    d=diractual()
  end
  if ptCommand[n]=="?" then
    v=""
    for i,w in ipairs(tDefDir[d].subdirs) do
      v=v.."["..w.."] "
    end
    for k,w in pairs(tDefDir[d].commands) do
      v=v..k.." "
    end
  elseif iscommand(d,ptCommand[n]) then
    if tDefDir[d].commands[ptCommand[n]].command=="" then
      v=string.format("%s\n","not command defined")
    else
      v=string.format("%s\n",tDefDir[d].commands[ptCommand[n]].command)
    end
  else
    v=string.format("%s\n","Not directory or command")
  end
  io.write(v)
end

function exec_cd(ptCommand)
  if(ptCommand[2]=="..") then
    if(table.maxn(tDir)>0) then
      table.remove(tDir)
    end
  elseif string.sub(ptCommand[2],1,1)=="." then
    local d=string.sub(ptCommand[2],2)
    local vdir={}
    local sd
    local n=0
    table.insert(vdir,d)
    repeat
      sd=""
      n=n+1
      for k,v in pairs(tDefDir) do
        if v.subdirs~=nil then
          for i,w in ipairs(v.subdirs) do
            if w==d then
              sd=k
              break
            end
          end
          if sd~="" then
            break
          end
        end
      end
      if sd=="root" then
        break
      elseif sd~="" then
        table.insert(vdir,sd)
        d=sd
      end
    until n>10 or sd==""  
    if sd=="root" then
      local x=table.maxn(tDir)
      if (x>0) then
        for i=1,x do
          table.remove(tDir)
        end
      end
      n=table.maxn(vdir)
      for i=n,1,-1 do
        table.insert(tDir,vdir[i])
      end
    else
      print ("Error path not found: "..arraytostring(di))
    end
  else
    local vdir={}
    local n=0
    local di=""
    local nd=0
    for w in string.gmatch(ptCommand[2], "[_%w]+") do
      table.insert(vdir,w)
      n=n+1
    end
    if (string.sub(ptCommand[2],1,1)=="/") then
      di="root"
    else
      nd=table.maxn(tDir)
      if (nd==0) then
        di="root"
      else
        di=tDir[nd]
      end
    end
    local vdi=di
    for i=1,n do
      if (isdir(vdi,vdir[i])) then
        vdi=vdir[i]
      else
        print(vdir[i].." No such directory")
        return 1
      end
    end
    local x=table.maxn(tDir)
    if (nd==0 and x>0) then
      for i=1,x do
        table.remove(tDir)
      end
    end
    for i=1,n do
      table.insert(tDir,vdir[i])
    end
  end
end

function isdir(pparent,pdir)
  local ret=false
  if(pparent=="") then
    for k,v in pairs(tDefDir) do
      if(k==pdir) then
        ret=true
        break
      end
    end
  else
    local n=table.maxn(tDefDir[pparent].subdirs)
    for w=1,n do
      if (tDefDir[pparent].subdirs[w]==pdir) then
        ret=true
        break
      end
    end
  end
  return ret
end

function iscommand(pparent,pcommand)
  local ret=false
  local vc=""
  local d=""
  if(pparent=="") then
    d=diractual()
  else
    d=pparent
  end
  for k,v in pairs(tDefDir[d].commands) do
    if(k==pcommand) then
      ret=true
      break
    end
  end
  return ret
end


function diractual()
  local n=table.maxn(tDir)
  if (n==0) then
    d="root"
  else
    d=tDir[n]
  end
  return d
end

function trim(ptext)
  if(ptext==nil) then
    return ""
  else
    local n=string.find(ptext,"%S")
    return string.sub(ptext,n)
  end
end

function init()
  tDefDir={}
  tDir={}
  vInput=""
  version="0.02"
  print ("cliorc version "..version)
  if (loadDefs(tDefDir)) then
    io.write(string.format("\n%s\n","type help for help"))
    return 0
  else
    return 1
  end
end

function call_cexec(pinput)
  parse(pinput)
  return 0
end

function getprompt()
  local d=""
  if tDir[1]==nil then
    d="/"
  end
  for i,w in ipairs(tDir) do
    d=d.."/"..w
  end
  d=d..">"
  return d
end

function luarun()
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

luainterp=0
if luainterp==1 then
  if init()==0 then
    luarun()
  end
end

