# MGGO中国API - URL Token认证方案

## 问题解决方案

针对iOS Safari/Chrome在iframe中的第三方Cookie限制问题，我们实现了URL Token认证方案。

## 实现原理

1. **不使用Cookie** - 所有认证信息通过URL参数传递
2. **Token验证** - 每次请求都通过token验证身份
3. **SessionStorage** - 使用sessionStorage代替Cookie存储临时数据

## API端点

### 1. 生成游戏Token
```
POST https://mggapi-cn.onrender.com/api/launch/token/generate
```

请求示例：
```json
{
    "operator": "VP_TEST",
    "user_id": "test_001", 
    "username": "TestUser",
    "currency": "CNY"
}
```

响应示例：
```json
{
    "status": "success",
    "data": {
        "token": "mggo_VP_TEST_test_001_1750531755",
        "game_url": "https://mggapi-cn.onrender.com/game/launch?token=mggo_VP_TEST_test_001_1750531755",
        "expires_in": 3600
    }
}
```

### 2. 游戏启动页面
```
GET https://mggapi-cn.onrender.com/game/launch?token={token}
```

这个页面会：
- 验证token有效性
- 将token存储在sessionStorage（不是cookie）
- 通过postMessage与父窗口通信
- 加载游戏时将token作为URL参数传递

### 3. Token验证
```
POST https://mggapi-cn.onrender.com/api/verify-token
```

## 集成步骤

### VP大厅端实现

```javascript
// 1. 生成游戏启动Token
async function launchMGGOGame(userId, username) {
    const response = await fetch('https://mggapi-cn.onrender.com/api/launch/token/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            operator: 'VP_PROD',
            user_id: userId,
            username: username,
            currency: 'CNY'
        })
    });
    
    const data = await response.json();
    
    // 2. 在iframe中打开游戏
    const gameFrame = document.getElementById('game-iframe');
    gameFrame.src = data.data.game_url;
}

// 3. 监听游戏消息
window.addEventListener('message', function(e) {
    if (e.data.type === 'mggo_game_loaded') {
        console.log('游戏加载成功，token:', e.data.token);
    }
});
```

### MGGO游戏端实现

```javascript
// 从URL获取token
const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');

// 或从sessionStorage获取
const storedToken = sessionStorage.getItem('mggo_token');

// 使用token进行API调用
async function makeAPICall(endpoint, data) {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Game-Token': token // 通过header传递token
        },
        body: JSON.stringify(data)
    });
    
    return response.json();
}
```

## 测试步骤

1. 在iPhone Chrome/Safari中访问VP大厅
2. 点击进入MGGO游戏
3. 确认不需要重新登录
4. 检查游戏是否正常加载

## 注意事项

1. **Token有效期** - 默认1小时，可根据需要调整
2. **安全性** - Token应该包含签名验证
3. **跨域设置** - 确保CORS headers正确配置

## 技术支持

如有问题，请提供：
- 浏览器版本
- 具体错误信息
- Network请求截图

---

此方案完全避免使用Cookie，解决了iOS iframe认证问题。