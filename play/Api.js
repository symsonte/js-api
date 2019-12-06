import {api} from "@yosmy/request";
import Platform from "@yosmy/platform";
import uniq from "lodash/uniq";
import uniqWith from "lodash/uniqWith";
import union from "lodash/union";
import unionBy from "lodash/unionBy";

const server = __DEV__ ? "http://192.168.1.14" : "https://api.prod.com";

const hash = (str) => {
    let hash = 0, i, chr;
    
    if (str.length === 0) { 
        return hash;
    }
    
    for (i = 0; i < str.length; i++) {
        chr = str.charCodeAt(i);
    
        hash = ((hash << 5) - hash) + chr;
    
        hash |= 0; // Convert to 32bit integer
    }
    
    return hash;
};


const Api = {
    addUser: (
        firstname,
        lastname,
        onReturn,
        onUserInvalidFirstnameException,
        onUserInvalidLastnameException,
        onUnknownException,
        onConnectionException,
        onServerException
    ) => {
        const resolve = (response, onReturn, onUserInvalidFirstnameException, onUserInvalidLastnameException, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                case 'user.invalid-firstname-exception':
                    onUserInvalidFirstnameException(payload);
                    
                    break;
                case 'user.invalid-lastname-exception':
                    onUserInvalidLastnameException(payload);
                    
                    break;
                default:
                    onUnknownException(response);
            }
        };
        
        api(
            server + '/add-user',
            null,
            null,
            {
                firstname: firstname,
                lastname: lastname
            }
            
        )
            .then((response) => {
                resolve(response, onReturn, onUserInvalidFirstnameException, onUserInvalidLastnameException, onUnknownException);    
            })
            .catch((response) => {
                const {code} = response;
                
                switch (code) {
                    case "connection":
                        onConnectionException();
                    
                        break;
                    case "server":
                        onServerException();
                    
                        break;
                    default:
                        onUnknownException(response);
                }
            });
        
    },
    collectUpdatedUsers: (
        token,
        ids,
        onReturn,
        onUnknownException,
        onConnectionException,
        onServerException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        Platform.cache.get("/collect-updated-users")
            .then((cache) => {
                const now = Date.now();
                
                Promise.all([
                    // Items with no cache
                    new Promise((resolve) => {
                        let newIds = ids;
                        
                        if (cache) {
                            // Just get ids with no cache
                            newIds = newIds.filter((id) => {
                                return cache.table.indexOf(id) === -1
                            });
                            
                            if (newIds.length === 0) {
                                // No need to call api
                                resolve([]);
                                
                                return;
                            }
                        }
                        
                        
                        api(
                            server + '/collect-updated-users',
                            null,
                            token,
                            {
                                ids: newIds,
                                updated: null
                            }
                            
                        )
                            .then((response) => {
                                
                                resolve(response.payload);
                                
                                if (response.payload.length > 0) {
                                    Platform.cache.get("/collect-updated-users")
                                        .then((cache) => {
                                            if (!cache) {
                                                cache = {
                                                    table: [],
                                                    response: {
                                                        payload: []
                                                    },
                                                    date: now
                                                };
                                            }
                                        
                                            Platform.cache.set(
                                                `/collect-updated-users`, 
                                                {
                                                    ...cache,
                                                    table: cache.table.concat(newIds),
                                                    response: {
                                                        ...cache.response,
                                                        payload: cache.response.payload.concat(response.payload)
                                                    }
                                                }
                                            ).catch(console.log);
                                        });
                                }            
                                    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case "connection":
                                        onConnectionException();
                                    
                                        break;
                                    case "server":
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                    // Items with cache
                    new Promise((resolve) => {
                        let cacheIds = ids;
                    
                        if (!cache) {
                            resolve([]);
                        }
                        
                        // Just get ids in cache
                        cacheIds = cacheIds.filter((id) => {
                            return cache.table.indexOf(id) !== -1
                        });
        
                        // No items in cache because everything is new?
                        if (cacheIds.length === 0) {
                            resolve([]);
                            
                            return;
                        }
                        
                        // Cache not expired?
                        if (cache.date + 60000 >= now) {
                            // Return just requested items, not the whole cache
                            resolve(cache.response.payload.filter(({id}) => {
                                return cacheIds.indexOf(id) !== -1 
                            }));
                            
                            return;
                        }
                        
                        resolve(cache.response.payload);
        
                        
                        api(
                            server + '/collect-updated-users',
                            null,
                            token,
                            {
                                ids: cacheIds,
                                updated: cache.date
                            }
                            
                        )
                            .then((response) => {
                                
                                if (response.payload.length > 0) {
                                    Platform.cache.get("/collect-updated-users")
                                        .then((cache) => {
                                            cache = {
                                                ...cache,
                                                date: now
                                            };
                                            
                                            cache = {
                                                ...cache,
                                                response: {
                                                    payload: unionBy(
                                                        response.payload,
                                                        cache.response.payload,
                                                        "id"
                                                    )
                                                },
                                            };
                                            
                                            Platform.cache.set(
                                                `/collect-updated-users`, 
                                                cache
                                            ).catch(console.log);
                                        })
                                }
                                    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case "connection":
                                        onConnectionException();
                                    
                                        break;
                                    case "server":
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                ])
                    .then((result) => {
                        const response = {
                            code: "return",
                            payload: []
                                .concat(result[0])
                                .concat(result[1])
                        };
                        
                        resolve(response, onReturn, onUnknownException);
                    });
            });
        
    },
    collectUsers: (
        token,
        ids,
        onReturn,
        onUnknownException,
        onConnectionException,
        onServerException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        Platform.cache.get("/collect-users")
            .then((file) => {
                const now = Date.now();
                
                Promise.all([
                    // Items with no cache
                    new Promise((resolve) => {
                        if (file) {
                            const {table} = file;
                    
                            // Just get ids with no cache
                            ids = ids.filter((id) => {
                                return table.indexOf(id) === -1
                            });
                            
                            if (ids.length === 0) {
                                // No need to call api
                                resolve({table: [], payload: []});
                                
                                return;
                            }
                        }
                        
                        
                        api(
                            server + '/collect-users',
                            null,
                            token,
                            {
                                ids: ids
                            }
                            
                        )
                            .then((response) => {
                                resolve({table: ids, payload: response.payload})    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case "connection":
                                        onConnectionException();
                                    
                                        break;
                                    case "server":
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                    // Items with cache
                    new Promise((resolve) => {
                        if (!file) {
                            resolve({table: [], payload: []});
                        }
                        
                        let {date, table, response} = file;
        
                        // Cache not expired?
                        if (date + 60000 >= now) {
                            resolve({table: table, payload: response.payload});
                            
                            return;
                        }
                        
                        
                        api(
                            server + '/collect-users',
                            null,
                            token,
                            {
                                ids: table
                            }
                            
                        )
                            .then((response) => {
                                resolve({table: table, payload: response.payload});    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case "connection":
                                        onConnectionException();
                                    
                                        break;
                                    case "server":
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                ])
                    .then((result) => {
                        let table = union(
                            result[0].table,
                            result[1].table
                        );
                        
                        let payload = unionBy(
                            result[0].payload,
                            result[1].payload,
                            "id"
                        );
                        
                        // Remove duplicated
                        
                        table = uniq(table);
                        
                        payload = uniqWith(
                            payload,
                            (a, b) => {
                                return a.id === b.id;
                            }
                        );
                        
                        const response = {
                            code: "return",
                            payload: payload
                        };
                        
                        Platform.cache.set(`/collect-users`, {table: table, response: response, date: now}).catch(console.log);
                        
                        resolve(response, onReturn, onUnknownException);
                    });
            });
        
    },
    pickUser: (
        token,
        id,
        onReturn,
        onUnknownException,
        onConnectionException,
        onServerException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        Platform.cache.get(`/pick-user-${hash(id)}`)
            .then((file) => {
                if (file) {
                    const {response} = file;
                        
                    resolve(response, onReturn, onUnknownException);
                    
                    return;
                }
                
                
        api(
            server + '/pick-user',
            null,
            token,
            {
                id: id
            }
            
        )
            .then((response) => {
                
                Platform.cache.set(`/pick-user-${hash(id)}`, {response: response}).catch(console.log);
                
                resolve(response, onReturn, onUnknownException);    
            })
            .catch((response) => {
                const {code} = response;
                
                switch (code) {
                    case "connection":
                        onConnectionException();
                    
                        break;
                    case "server":
                        onServerException();
                    
                        break;
                    default:
                        onUnknownException(response);
                }
            });
        
            });
        
    },
    removeUser: (
        id,
        onReturn,
        onUnknownException,
        onConnectionException,
        onServerException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        api(
            server + '/remove-user',
            null,
            null,
            {
                id: id
            }
            
        )
            .then((response) => {
                Platform.cache.delete(`/pick-user-${hash(id)}`).catch(console.log);
                
                resolve(response, onReturn, onUnknownException);    
            })
            .catch((response) => {
                const {code} = response;
                
                switch (code) {
                    case "connection":
                        onConnectionException();
                    
                        break;
                    case "server":
                        onServerException();
                    
                        break;
                    default:
                        onUnknownException(response);
                }
            });
        
    },
    user: {
        updateFirstname: (
            id,
            firstname,
            onReturn,
            onNonexistentUserException,
            onUnknownException,
            onConnectionException,
            onServerException
        ) => {
            const resolve = (response, onReturn, onNonexistentUserException, onUnknownException) => {
                const {code, payload} = response;
            
                switch (code) {
                    case 'return':
                        onReturn(payload);
            
                        break;
                    case 'nonexistent-user-exception':
                        onNonexistentUserException(payload);
                        
                        break;
                    default:
                        onUnknownException(response);
                }
            };
            
            api(
                server + '/user/update-firstname',
                null,
                null,
                {
                    id: id,
                    firstname: firstname
                }
                
            )
                .then((response) => {
                    resolve(response, onReturn, onNonexistentUserException, onUnknownException);    
                })
                .catch((response) => {
                    const {code} = response;
                    
                    switch (code) {
                        case "connection":
                            onConnectionException();
                        
                            break;
                        case "server":
                            onServerException();
                        
                            break;
                        default:
                            onUnknownException(response);
                    }
                });
            
        }
    }
};

const WrappedApi = (
    session, 
    token, 
    onUnknownException,
    onConnectionException,
    onServerException
) => {
    return {
        addUser: (
            ...props
        ) => {
            Api.addUser(
                ...props,
                onUnknownException,
                onConnectionException,
                onServerException
            )
        },
        collectUpdatedUsers: (
            ...props
        ) => {
            Api.collectUpdatedUsers(
                token,
                ...props,
                onUnknownException,
                onConnectionException,
                onServerException
            )
        },
        collectUsers: (
            ...props
        ) => {
            Api.collectUsers(
                token,
                ...props,
                onUnknownException,
                onConnectionException,
                onServerException
            )
        },
        pickUser: (
            ...props
        ) => {
            Api.pickUser(
                token,
                ...props,
                onUnknownException,
                onConnectionException,
                onServerException
            )
        },
        removeUser: (
            ...props
        ) => {
            Api.removeUser(
                ...props,
                onUnknownException,
                onConnectionException,
                onServerException
            )
        },
        user: {
            updateFirstname: (
                ...props
            ) => {
                Api.user.updateFirstname(
                    ...props,
                    onUnknownException,
                    onConnectionException,
                    onServerException
                )
            }
        }
    };
};

export default WrappedApi;