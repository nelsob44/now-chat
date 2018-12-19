<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-3">
                <div class="card card-default">
                    <div class="card-header">
                        Now Chat
                    </div>
                        <ul class="list-group">
                            <a href="" @click.prevent="openChat(friend)" v-for="friend in friends" :key="friend.id">
                                <li class="list-group-item">
                                {{friend.name}}
                                </li>
                            </a>
                        </ul>
                </div>
            </div>
            <div class="col-md-9">
                <span v-for="friend in friends" :key="friend.id" v-if="friend.session">
                    <message-component v-if="friend.session.open" @close="close(friend)" :friend=friend></message-component>
                </span>
            </div>
        </div>
    </div>
</template>

<script>
    import MessageComponent from './MessageComponent';
    export default {
        data(){
            return {
                open: true,
                friends: []
            }
        },
        methods:{
            close(friend){
                friend.session.open = false;
            },
            getFriends(){
                axios.post('getFriends').then(res => this.friends = res.data.data);
            },
            openChat(friend){
                if(friend.session){
                    this.friends.forEach(friend => {
                    friend.session.open = false
                });
                friend.session.open = true;
                }else {
                    this.createSession(friend);                    
                }
            },

            createSession(friend){
                axios.post('session/create', {friend_id:friend.id}).then(res => {
                    (friend.session = res.data.data), (friend.session.open = true);
                });
            },
        },
        created(){
            this.getFriends();
        },
        components:{MessageComponent},
        
    }
</script>
