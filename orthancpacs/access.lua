local function starts_with(str, start)
   return str:sub(1, #start) == start
end

function IncomingHttpRequestFilter(method, uri, ip, username, httpHeaders)
    -- if dicomWeb login check to make only dicomweb and wado APIs with get method available (because externally exposed through PHP proxy)
    if (username == 'dicomWeb') then
        if ( (starts_with(uri, '/dicom-web') or starts_with(uri, '/wado')) and method=='get') then
        return true
        else return false
        end
    end

end



