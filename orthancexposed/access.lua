local function starts_with(str, start)
   return str:sub(1, #start) == start
end

function IncomingHttpRequestFilter(method, uri, ip, username, httpHeaders)
    -- if localhost ip or php login allow acces to all apis
    if (starts_with(ip, '172.') and username == 'internal') then
        return true
    -- if external login only accept incoming DICOM for transfer accelerator
    elseif (starts_with(uri, '/transfers/push') and username == 'external') then
        return true
    else
        return false
    end

end



